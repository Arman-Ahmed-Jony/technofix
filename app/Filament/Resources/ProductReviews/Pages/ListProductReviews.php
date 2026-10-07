<?php

namespace App\Filament\Resources\ProductReviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviews\ProductReviewResource;
use App\Models\ProductReview;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;

    public function getTabs(): array
    {
        $waiting = ProductReview::where('status', ReviewStatus::Pending)->count();

        return [
            'waiting' => Tab::make('Waiting for approval')->badge($waiting ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('status', ReviewStatus::Pending)),
            'published' => Tab::make('Published')->modifyQueryUsing(fn ($query) => $query->where('status', ReviewStatus::Approved)),
            'hidden' => Tab::make('Hidden')->modifyQueryUsing(fn ($query) => $query->where('status', ReviewStatus::Rejected)),
            'all' => Tab::make('All'),
        ];
    }
}
