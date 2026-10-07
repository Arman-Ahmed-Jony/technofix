<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Actions\AdjustStockAction;
use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdjustStockAction::make(),
            HistoryAction::make(),
            Action::make('open')
                ->label('View on site')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->getRecord()->url, shouldOpenInNewTab: true)
                ->visible(fn () => $this->getRecord()->status === ProductStatus::Published && ! $this->getRecord()->trashed()),
            ActionGroup::make([
                ProductResource::duplicateAction(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }
}
