<?php

namespace App\Filament\Resources\NotFoundUrls;

use App\Filament\Resources\NotFoundUrls\Pages\ListNotFoundUrls;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Models\NotFoundUrl;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * "Broken links": addresses visitors or search engines asked for that don't exist (logged by App\Support\NotFound).
 * The fix is usually a redirect to the right page — one click from here.
 */
class NotFoundUrlResource extends Resource
{
    protected static ?string $model = NotFoundUrl::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link-slash';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Broken links';

    protected static ?string $modelLabel = 'broken link';

    protected static ?int $navigationSort = 91;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('(hits - bot_hits) desc')->orderByDesc('last_seen_at'))
            ->columns([
                TextColumn::make('path')
                    ->label('Address')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->wrap()
                    ->url(fn (NotFoundUrl $record) => url($record->path), shouldOpenInNewTab: true)
                    ->description(fn (NotFoundUrl $record) => $record->referrer ? 'Linked from '.Str::limit(preg_replace('~^https?://(www\.)?~', '', $record->referrer), 70) : null),
                TextColumn::make('people')
                    ->label('Visitors')
                    ->state(fn (NotFoundUrl $record) => $record->peopleHits())
                    ->numeric()
                    ->alignEnd()
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw('(hits - bot_hits) '.($direction === 'asc' ? 'asc' : 'desc'))),
                TextColumn::make('bot_hits')->label('Google & bots')->numeric()->alignEnd()->sortable(),
                TextColumn::make('last_seen_at')->label('Last tried')->since()->sortable(),
                TextColumn::make('first_seen_at')->label('First seen')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('people')->label('Visited by people')->query(fn (Builder $query) => $query->whereColumn('hits', '>', 'bot_hits')),
                Filter::make('recent')->label('Tried in the last 7 days')->query(fn (Builder $query) => $query->where('last_seen_at', '>=', now()->subDays(7))),
            ])
            ->recordActions([
                Action::make('redirect')
                    ->label('Redirect')
                    ->icon('heroicon-o-arrow-uturn-right')
                    ->button()
                    ->size('sm')
                    ->modalHeading(fn (NotFoundUrl $record) => 'Redirect '.Str::limit($record->path, 60))
                    ->modalDescription('Visitors and Google will be sent to the page you pick.')
                    ->modalSubmitActionLabel('Add redirect')
                    ->schema(RedirectResource::targetFields())
                    ->action(function (NotFoundUrl $record, array $data) {
                        $redirect = Redirect::updateOrCreate(
                            ['from_path' => Redirect::normalize($record->path)],
                            ['to_url' => $data['to_url'], 'status' => (int) $data['status'], 'is_active' => true, 'note' => 'From broken links'],
                        );
                        $record->delete();

                        Notification::make()->success()->title('Redirect added')->body($redirect->from_path.' → '.$redirect->to_url)->send();
                    }),
                DeleteAction::make()->label('Remove')->modalHeading('Remove from the list')->modalDescription('It comes back if someone tries the address again.'),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->label('Remove selected')])])
            ->emptyStateHeading('No broken links')
            ->emptyStateDescription('Addresses people or search engines try that don’t exist will be listed here.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotFoundUrls::route('/'),
        ];
    }
}
