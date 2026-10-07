<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\BuilderRole;
use App\Enums\LeadType;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PcBuilderSlot;
use App\Models\Product;
use App\Models\SavedBuild;
use App\Settings\PcBuilderSettings;
use App\Settings\StoreSettings;
use App\Support\Blocklist;
use App\Support\Catalog;
use App\Support\IconSprite;
use App\Support\PcBuilder\Build;
use App\Support\PcBuilder\Compatibility;
use App\Support\PcBuilder\Specs;
use App\Support\Phone;
use App\Support\Tracking;
use App\Support\Turnstile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The PC Builder page. The build lives in the browser as a short code ("slotId:productId,…", see Build) and is
 * sent with every request; prices, compatibility and totals are always worked out here.
 */
class PcBuilderController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(protected Compatibility $compatibility) {}

    public function show(Request $request, PcBuilderSettings $settings, IconSprite $sprite): View
    {
        $slots = $this->slots();

        // Icons the page draws from JSON (slot + product category icons) must be in the page's sprite.
        $slots->flatMap(fn (PcBuilderSlot $slot) => [$slot->icon ?: 'package', ...collect($slot->allCategoryIds())
            ->map(fn ($id) => app(Catalog::class)->categoryById($id)?->icon)->filter()])
            ->unique()->each(fn (string $icon) => $sprite->use($icon));

        return view('storefront.pc-builder', [
            'settings' => $settings,
            'slots' => $slots->map(fn (PcBuilderSlot $slot) => [
                'id' => $slot->id,
                'name' => $slot->name,
                'description' => $slot->description,
                'icon' => $slot->icon ?: 'package',
                'section' => $slot->section,
                'required' => $slot->is_required,
                'max' => $slot->max_qty,
            ])->values(),
            'sections' => PcBuilderSlot::SECTIONS,
            'initial' => $request->filled('b') ? $this->payload(Build::fromCode($request->query('b'))) : null,
            'customer' => ($customer = $request->user('customer')) ? ['name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email] : null,
        ]);
    }

    /** The build as the page shows it: picked parts, total, power and compatibility. */
    public function state(Request $request): JsonResponse
    {
        return response()->json($this->payload(Build::fromCode($request->query('b'))));
    }

    /** Products for one slot, each assessed against the rest of the build; compatible parts first. */
    public function options(Request $request, PcBuilderSlot $slot): JsonResponse
    {
        abort_unless($slot->is_active, 404);

        $build = Build::fromCode($request->query('b'));
        $query = $slot->products()->with('brand');
        $terms = collect(preg_split('/\s+/', Str::lower(trim((string) $request->query('q')))))->filter()->take(6);

        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('model', 'like', $like)->orWhere('sku', 'like', $like));
        }

        $all = $query->get();
        $brands = $all->filter->brand_id->groupBy('brand_id')
            ->map(fn (Collection $group) => ['id' => $group->first()->brand_id, 'name' => $group->first()->brand_name, 'count' => $group->count()])
            ->sortBy('name')->values();

        $brandIds = array_map('intval', (array) $request->query('brand', []));
        $rank = ['ok' => 0, 'unchecked' => 1, 'warning' => 2, 'conflict' => 3];

        $rows = $all
            ->when($brandIds, fn (Collection $c) => $c->whereIn('brand_id', $brandIds))
            ->when($request->boolean('available'), fn (Collection $c) => $c->filter(fn (Product $p) => self::available($p)))
            ->map(fn (Product $product) => ['product' => $product, 'fit' => $this->compatibility->assess($build, $slot, $product)])
            ->when($request->boolean('compatible'), fn (Collection $c) => $c->reject(fn (array $row) => $row['fit']['status'] === 'conflict'));

        $rows = match ($request->query('sort')) {
            'price_asc' => $rows->sortBy(fn (array $row) => [self::available($row['product']) ? 0 : 1, (int) $row['product']->sale_price]),
            'price_desc' => $rows->sortBy(fn (array $row) => [self::available($row['product']) ? 0 : 1, -(int) $row['product']->sale_price]),
            default => $rows->sortBy(fn (array $row) => [$rank[$row['fit']['status'] ?? 'unchecked'], self::available($row['product']) ? 0 : 1, (int) $row['product']->sale_price]),
        };

        $page = max(1, (int) $request->query('page', 1));

        return response()->json([
            'products' => $rows->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)
                ->map(fn (array $row) => [...self::card($row['product']), 'fit' => $row['fit']])->values(),
            'brands' => $brands,
            'total' => $rows->count(),
            'page' => $page,
            'pages' => max(1, (int) ceil($rows->count() / self::PER_PAGE)),
        ]);
    }

    /** The quotation as a PDF download (dompdf, pure PHP — works on shared hosting). */
    public function quote(Request $request): Response|RedirectResponse
    {
        $build = Build::fromCode($request->query('b'));

        if ($build->isEmpty()) {
            return redirect()->route('pc-builder');
        }

        $data = $this->quoteData($build);

        return Pdf::loadView('pdf.pc-quote', $data)->setPaper('a4')->download("TechnoFix-PC-quotation-{$data['number']}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    public function quoteData(Build $build): array
    {
        return [
            'build' => $build,
            'issues' => $this->compatibility->issues($build),
            'power' => $this->compatibility->power($build),
            'settings' => app(PcBuilderSettings::class),
            'store' => app(StoreSettings::class),
            'link' => Build::link($build->encode()),
            'number' => 'PC-'.now()->format('ymd-Hi'),
            'date' => now(),
        ];
    }

    /** Keep the build in the customer's account (signed-in customers only). */
    public function save(Request $request): JsonResponse
    {
        $customer = $request->user('customer');

        if (! $customer) {
            return response()->json(['message' => 'Please sign in to save builds.', 'login' => route('account.login', ['to' => 'pc-builder'])], 401);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'code' => ['required', 'string', 'max:500']]);
        $build = Build::fromCode($data['code']);

        if ($build->isEmpty()) {
            return response()->json(['message' => 'Add some parts before saving.'], 422);
        }

        if ($customer->savedBuilds()->count() >= SavedBuild::MAX_PER_CUSTOMER) {
            return response()->json(['message' => 'You can keep up to '.SavedBuild::MAX_PER_CUSTOMER.' builds. Delete one in your account first.'], 422);
        }

        $customer->savedBuilds()->create(['name' => $data['name'], 'code' => $build->encode()]);

        return response()->json(['message' => 'Saved to your account.', 'url' => route('account.builds')]);
    }

    /** "Send to our team": the build becomes a lead (Admin → Leads) so staff can call back with advice. */
    public function enquiry(Request $request): JsonResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['required', 'regex:'.Phone::PATTERN, Blocklist::rule()],
            'email' => ['nullable', 'email', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'code' => ['required', 'string', 'max:500'],
            'website' => ['nullable'], // honeypot
            ...Turnstile::rules('pc_builder'),
        ], ['phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.', ...Turnstile::messages()]);

        $build = Build::fromCode($data['code']);

        if ($build->isEmpty()) {
            return response()->json(['message' => 'Add some parts before sending your build.'], 422);
        }

        if (blank($data['website'] ?? null)) {
            $issues = $this->compatibility->issues($build);
            $parts = collect($build->items)->map(fn (array $item, int $slotId) => $build->slots[$slotId]->name.': '.$item['product']->name
                .($item['qty'] > 1 ? " × {$item['qty']}" : '').' — BDT '.number_format((int) $item['product']->sale_price * $item['qty']));

            $lead = Lead::create([
                'type' => LeadType::PcBuild,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'subject' => 'PC build — BDT '.number_format($build->subtotal()).' ('.$parts->count().' parts)',
                'message' => trim(($data['note'] ?? '')."\n\n".$parts->join("\n")."\nTotal: BDT ".number_format($build->subtotal())),
                'meta' => array_filter([
                    'Build link' => Build::link($build->encode()),
                    'Compatibility' => $issues ? collect($issues)->pluck('message')->join(' ') : 'No problems found',
                    'Still needed' => $build->missing()->pluck('name')->join(', ') ?: null,
                    'Customer account' => $request->user('customer')?->phone,
                ]),
                'page_url' => route('pc-builder'),
                'attribution' => Tracking::attribution($request),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);
        }

        return response()->json(array_filter([
            'message' => 'Thanks! Our team will call you shortly about your build.',
            'track' => isset($lead) ? app(Tracking::class)->lead($lead, $request, flash: false) : null,
        ]));
    }

    /**
     * @return Collection<int, PcBuilderSlot> active slots that have something to pick
     */
    protected function slots(): Collection
    {
        return PcBuilderSlot::query()->active()->with('filterAttribute')->get()
            ->filter(fn (PcBuilderSlot $slot) => $slot->products()->exists())
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Build $build): array
    {
        $power = $this->compatibility->power($build);
        $psu = $build->pick(BuilderRole::PowerSupply);

        return [
            'code' => $build->encode(),
            'items' => (object) collect($build->items)->map(fn (array $item) => [...self::card($item['product']), 'qty' => $item['qty']])->all(),
            'subtotal' => $build->subtotal(),
            'count' => (int) collect($build->items)->sum('qty'),
            'issues' => $this->compatibility->issues($build),
            'missing' => $build->missing()->pluck('id')->values(),
            'power' => [
                'estimate' => $power['estimate'],
                'recommended' => $power['recommended'],
                'known' => $power['parts'] !== [],
                'psu' => $psu ? Specs::number($psu['product'], 'psu_wattage') : null,
            ],
        ];
    }

    /** Can be ordered now (in stock / pre-order, with a price). */
    public static function available(Product $product): bool
    {
        return $product->stock->isPurchasable() && $product->sale_price !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'url' => $product->url,
            'image' => $product->image,
            'icon' => $product->icon,
            'brand' => $product->brand_name,
            'category' => $product->category_name,
            'price' => $product->sale_price,
            'regular_price' => $product->list_price,
            'available' => self::available($product),
            'stock' => ['label' => $product->stock->label(), 'dot' => $product->stock->dotClass()],
            'specs' => $product->cardSpecs(),
        ];
    }
}
