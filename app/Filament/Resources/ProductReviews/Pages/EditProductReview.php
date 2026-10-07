<?php

namespace App\Filament\Resources\ProductReviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductReview extends EditRecord
{
    protected static string $resource = ProductReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [HistoryAction::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $reply = trim((string) ($data['reply'] ?? ''));
        $data['reply'] = $reply !== '' ? $reply : null;
        $data['replied_at'] = $reply !== '' ? ($this->record->reply === $reply ? $this->record->replied_at : now()) : null;

        $status = $data['status'] instanceof ReviewStatus ? $data['status'] : ReviewStatus::tryFrom((string) $data['status']);
        if ($status === ReviewStatus::Approved && ! $this->record->approved_at) {
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
