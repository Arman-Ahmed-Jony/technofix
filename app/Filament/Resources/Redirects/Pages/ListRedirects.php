<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    public function getSubheading(): ?string
    {
        return 'Old addresses that send visitors and Google to a new page. Renamed products, categories, brands, pages and services get one automatically.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSeveral')
                ->label('Add several')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->modalHeading('Add several redirects')
                ->modalDescription('One per line: the old address, a space (or comma), then the new address. Handy for links from an old website.')
                ->modalSubmitActionLabel('Save redirects')
                ->schema([
                    Textarea::make('lines')
                        ->hiddenLabel()
                        ->required()
                        ->rows(10)
                        ->placeholder("/product/asus-vivobook-15/  /asus-vivobook-15-x1504va\n/product-category/laptops/*  /laptop\nhttps://old-site.com/contact-us/  /contact")
                        ->extraInputAttributes(['class' => 'font-mono']),
                    Radio::make('status')->label('Type')->options(Redirect::STATUSES)->default(301)->inline()->required(),
                ])
                ->action(fn (array $data) => $this->addSeveral($data['lines'], (int) $data['status'])),
            CreateAction::make(),
        ];
    }

    protected function addSeveral(string $lines, int $status): void
    {
        $saved = 0;
        $skipped = [];

        foreach (preg_split('/\R/', $lines) as $line) {
            if (($line = trim($line)) === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/\s*(?:→|->|=>|,|\t|\s)\s*/u', $line, -1, PREG_SPLIT_NO_EMPTY);
            $from = Redirect::normalize($parts[0] ?? '');

            if (count($parts) !== 2 || in_array($from, ['/', '/*'], true) || ! preg_match('~^(/|https?://)~i', $parts[1]) || $from === Redirect::normalize($parts[1])) {
                $skipped[] = Str::limit($line, 60);

                continue;
            }

            Redirect::updateOrCreate(['from_path' => $from], ['to_url' => $parts[1], 'status' => $status, 'is_active' => true, 'note' => 'Added in bulk']);
            $saved++;
        }

        Notification::make()
            ->title($saved.' '.Str::plural('redirect', $saved).' saved')
            ->body($skipped ? 'Skipped (not "old new"): '.implode(' · ', array_slice($skipped, 0, 5)).(count($skipped) > 5 ? ' …' : '') : null)
            ->status($skipped ? 'warning' : 'success')
            ->send();
    }
}
