<?php

namespace App\Filament\Actions;

use App\Enums\StaffArea;
use App\Models\Activity;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * "History" button on a record's page: its activity log entries (who changed what, newest first) in a side panel.
 */
class HistoryAction
{
    public static function make(): Action
    {
        return Action::make('history')
            ->label('History')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->visible(fn () => StaffArea::Activity->allowed())
            ->slideOver()
            ->modalWidth('lg')
            ->modalHeading('History')
            ->modalDescription('Changes made in the admin, newest first.')
            ->modalContent(fn (Model $record) => view('filament.activity.history', [
                'entries' => Activity::about($record)->with('user')->latest('id')->limit(100)->get(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }
}
