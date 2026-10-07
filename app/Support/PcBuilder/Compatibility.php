<?php

namespace App\Support\PcBuilder;

use App\Enums\BuilderRole;
use App\Models\PcBuilderSlot;
use App\Models\Product;
use App\Settings\PcBuilderSettings;

/**
 * The PC Builder's compatibility checks, run on the server only. Each check compares two picked parts using
 * their spec fields; when a value is missing the check is skipped (never a false alarm). Rules are switched
 * on/off in PcBuilderSettings.
 */
class Compatibility
{
    public function __construct(protected PcBuilderSettings $settings) {}

    /**
     * Every check that could run on this build.
     *
     * @return list<array{ok: bool, level: 'error'|'warning'|null, message: ?string, slots: list<int>}>
     */
    public function results(Build $build): array
    {
        $results = [];
        $s = $this->settings;
        $cpu = $build->pick(BuilderRole::Processor);
        $board = $build->pick(BuilderRole::Motherboard);

        if ($s->check_socket) {
            if ($cpu && $board && ($a = Specs::sockets($cpu['product'])) && ($b = Specs::sockets($board['product']))) {
                $results[] = $this->result(Specs::socketsMatch($a, $b), [$cpu, $board],
                    "The processor uses socket {$a[0]}, but the motherboard is ".implode(' / ', $b).'.');
            }

            $cooler = $build->pick(BuilderRole::CpuCooler);
            $partner = $cpu ?? $board;

            if ($cooler && $partner && ($c = Specs::sockets($cooler['product'])) && ($p = Specs::sockets($partner['product']))) {
                $results[] = $this->result(Specs::socketsMatch($c, $p), [$cooler, $partner],
                    "The CPU cooler doesn’t list socket {$p[0]} (it fits ".implode(', ', $c).').');
            }
        }

        if ($s->check_memory && $board && ($ram = $build->pick(BuilderRole::Memory))
            && ($r = Specs::memoryTypes($ram['product'])) && ($b = Specs::memoryTypes($board['product']))) {
            $results[] = $this->result((bool) array_intersect($r, $b), [$ram, $board],
                "The RAM is {$r[0]}, but the motherboard takes ".implode(' / ', $b).' memory.');
        }

        $case = $build->pick(BuilderRole::Casing);

        if ($s->check_form_factor && $board && $case
            && ($size = Specs::boardSize($board['product'])) && ($fits = Specs::boardSize($case['product']))) {
            $results[] = $this->result($size <= $fits, [$board, $case],
                'The motherboard is '.Specs::sizeName($size).' — too big for the casing (fits up to '.Specs::sizeName($fits).').');
        }

        if ($s->check_gpu_length && $case && ($gpu = $build->pick(BuilderRole::GraphicsCard))
            && ($length = Specs::number($gpu['product'], 'gpu_length')) && ($max = Specs::number($case['product'], 'max_gpu_length'))) {
            $results[] = $this->result($length <= $max, [$gpu, $case],
                "The graphics card is {$length} mm long; the casing fits cards up to {$max} mm.");
        }

        if ($s->check_power && ($psu = $build->pick(BuilderRole::PowerSupply)) && ($watts = Specs::number($psu['product'], 'psu_wattage'))) {
            $power = $this->power($build);

            if ($power['parts']) {
                $slots = [$psu, ...array_map(fn (int $id) => ['slot' => $build->slots[$id]], $power['parts'])];
                $results[] = match (true) {
                    $watts < $power['estimate'] => $this->result(false, $slots, "The power supply ({$watts} W) is below the estimated {$power['estimate']} W this build draws."),
                    $watts < $power['recommended'] => $this->result(false, $slots, "The power supply ({$watts} W) is cutting it close — we recommend at least {$power['recommended']} W.", 'warning'),
                    default => $this->result(true, $slots),
                };
            }
        }

        return $results;
    }

    /**
     * Problems to show on the build.
     *
     * @return list<array{level: 'error'|'warning', message: string, slots: list<int>}>
     */
    public function issues(Build $build): array
    {
        return collect($this->results($build))
            ->reject(fn (array $r) => $r['ok'])
            ->map(fn (array $r) => ['level' => $r['level'], 'message' => $r['message'], 'slots' => $r['slots']])
            ->values()->all();
    }

    /**
     * How a product would fit in a slot next to what's already picked: "ok" (checked and fits), "conflict"
     * (with reasons) or null (nothing to check against yet).
     *
     * @return array{status: 'ok'|'conflict'|'warning'|null, reasons: list<string>}
     */
    public function assess(Build $build, PcBuilderSlot $slot, Product $product): array
    {
        if (! $slot->role) {
            return ['status' => null, 'reasons' => []];
        }

        $mine = collect($this->results($build->with($slot, $product)))->filter(fn (array $r) => in_array($slot->id, $r['slots'], true));
        $errors = $mine->where('level', 'error');
        $warnings = $mine->where('level', 'warning');

        return match (true) {
            $errors->isNotEmpty() => ['status' => 'conflict', 'reasons' => $errors->pluck('message')->values()->all()],
            $warnings->isNotEmpty() => ['status' => 'warning', 'reasons' => $warnings->pluck('message')->values()->all()],
            $mine->isNotEmpty() => ['status' => 'ok', 'reasons' => []],
            default => ['status' => null, 'reasons' => []],
        };
    }

    /**
     * Estimated draw: processor + graphics card TDP (× qty) plus an allowance for everything else, and the
     * recommended PSU size (with headroom, rounded up to 50 W).
     *
     * @return array{estimate: int, recommended: int, parts: list<int>}
     */
    public function power(Build $build): array
    {
        $tdp = 0;
        $parts = [];

        foreach ($build->items as $slotId => $item) {
            if ($watts = Specs::number($item['product'], 'tdp')) {
                $tdp += $watts * $item['qty'];
                $parts[] = $slotId;
            }
        }

        $estimate = $tdp + $this->settings->base_watts;

        return [
            'estimate' => $estimate,
            'recommended' => (int) (ceil($estimate * (1 + $this->settings->psu_headroom / 100) / 50) * 50),
            'parts' => $parts,
        ];
    }

    /**
     * @param  list<array{slot: PcBuilderSlot}>  $picks
     */
    protected function result(bool $ok, array $picks, ?string $message = null, string $level = 'error'): array
    {
        return [
            'ok' => $ok,
            'level' => $ok ? null : $level,
            'message' => $ok ? null : $message,
            'slots' => array_values(array_unique(array_map(fn (array $pick) => $pick['slot']->id, $picks))),
        ];
    }
}
