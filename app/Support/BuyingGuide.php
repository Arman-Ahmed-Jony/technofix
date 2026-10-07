<?php

namespace App\Support;

use App\Settings\BuyingGuideSettings;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * The "Buying guide" box on category and category + brand pages: the page's own text and questions, or the
 * defaults from Admin → Content → Buying guide (with "{name}" filled in).
 */
class BuyingGuide
{
    /**
     * @param  list<array{question?: ?string, answer?: ?string}>|null  $faqs
     * @return array{name: string, html: string, faqs: list<array{question: string, answer: string}>}
     */
    public static function make(string $name, ?string $content = null, ?array $faqs = null): array
    {
        $defaults = app(BuyingGuideSettings::class);
        $complete = fn (?array $rows) => collect($rows ?? [])->filter(fn ($row) => filled($row['question'] ?? null) && filled($row['answer'] ?? null));
        $questions = $complete($faqs)->isNotEmpty() ? $complete($faqs) : $complete($defaults->faqs);

        return [
            'name' => $name,
            'html' => filled(strip_tags((string) $content))
                ? RichContentRenderer::make($content)->toHtml()
                : '<p>'.e(BuyingGuideSettings::withName($defaults->text, $name)).'</p>',
            'faqs' => $questions->map(fn (array $row) => [
                'question' => BuyingGuideSettings::withName($row['question'], $name),
                'answer' => BuyingGuideSettings::withName($row['answer'], $name),
            ])->values()->all(),
        ];
    }
}
