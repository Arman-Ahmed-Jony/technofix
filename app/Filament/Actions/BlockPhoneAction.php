<?php

namespace App\Filament\Actions;

use App\Support\Blocklist;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * "Block this number" on an order or lead (fake orders, refused deliveries): adds the phone to the blocklist in
 * Admin → Settings → Security & spam. Shows "Unblock number" once blocked.
 */
class BlockPhoneAction
{
    public static function make(): Action
    {
        return Action::make('blockPhone')
            ->label(fn (Model $record) => Blocklist::phoneBlocked($record->phone) ? 'Unblock number' : 'Block this number')
            ->icon(fn (Model $record) => Blocklist::phoneBlocked($record->phone) ? 'heroicon-o-lock-open' : 'heroicon-o-no-symbol')
            ->color(fn (Model $record) => Blocklist::phoneBlocked($record->phone) ? 'gray' : 'danger')
            ->visible(fn (Model $record) => filled($record->phone))
            ->requiresConfirmation()
            ->modalHeading(fn (Model $record) => (Blocklist::phoneBlocked($record->phone) ? 'Unblock ' : 'Block ').$record->phone.'?')
            ->modalDescription(fn (Model $record) => Blocklist::phoneBlocked($record->phone)
                ? 'This number can order and send forms on the website again.'
                : 'This number won’t be able to order, send enquiries or create an account on the website. You can still take orders by phone, and unblock it any time.')
            ->schema(fn (Model $record) => Blocklist::phoneBlocked($record->phone) ? [] : [
                TextInput::make('note')->label('Why (for staff)')->maxLength(120)->placeholder('Fake order, refused delivery'),
            ])
            ->action(function (Model $record, array $data) {
                if (Blocklist::phoneBlocked($record->phone)) {
                    Blocklist::unblock($record->phone);
                    Notification::make()->success()->title($record->phone.' unblocked')->send();

                    return;
                }

                Blocklist::block($record->phone, trim(($data['note'] ?? '').' ('.class_basename($record).' '.($record->number ?? '#'.$record->getKey()).')'));
                Notification::make()->success()->title($record->phone.' blocked')->send();
            });
    }
}
