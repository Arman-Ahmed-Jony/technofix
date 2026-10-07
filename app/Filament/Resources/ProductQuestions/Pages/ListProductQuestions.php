<?php

namespace App\Filament\Resources\ProductQuestions\Pages;

use App\Enums\QuestionStatus;
use App\Filament\Resources\ProductQuestions\ProductQuestionResource;
use App\Models\ProductQuestion;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListProductQuestions extends ListRecords
{
    protected static string $resource = ProductQuestionResource::class;

    public function getTabs(): array
    {
        $waiting = ProductQuestion::where('status', QuestionStatus::Pending)->count();

        return [
            'waiting' => Tab::make('Needs an answer')->badge($waiting ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('status', QuestionStatus::Pending)),
            'published' => Tab::make('Published')->modifyQueryUsing(fn ($query) => $query->where('status', QuestionStatus::Published)),
            'hidden' => Tab::make('Hidden')->modifyQueryUsing(fn ($query) => $query->where('status', QuestionStatus::Hidden)),
            'all' => Tab::make('All'),
        ];
    }
}
