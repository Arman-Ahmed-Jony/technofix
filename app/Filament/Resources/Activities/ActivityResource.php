<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Activity;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Activity log (read-only): who added, changed or deleted what in the admin, settings changes, imports, sign-ins.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static ?string $slug = 'activity';

    protected static ?int $navigationSort = 22;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->state(fn (Activity $record) => $record->user ? filament()->getUserAvatarUrl($record->user) : null)
                    ->circular()
                    ->imageSize(32),
                TextColumn::make('user_name')->label('Who')->weight('medium')->placeholder('—')->searchable(),
                TextColumn::make('action')
                    ->label('What')
                    ->state(fn (Activity $record) => $record->action())
                    ->description(fn (Activity $record) => $record->subject_label)
                    ->url(fn (Activity $record) => $record->subjectUrl()),
                TextColumn::make('changes')
                    ->label('Changes')
                    ->state(fn (Activity $record) => $record->changesHtml(3))
                    ->html()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->dateTimeTooltip('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')->label('Staff member')->relationship('user', 'name'),
                SelectFilter::make('subject_type')
                    ->label('What was changed')
                    ->options(fn () => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type')
                        ->mapWithKeys(fn (string $type) => [$type => Str::ucfirst(Activity::typeLabel($type))])
                        ->sort()
                        ->all()),
                SelectFilter::make('event')
                    ->label('Action')
                    ->options(['created' => 'Added', 'updated' => 'Changed', 'deleted' => 'Deleted', 'settings' => 'Settings', 'imported' => 'Imports', 'login' => 'Sign-ins']),
                Filter::make('when')
                    ->schema([DatePicker::make('from')->label('From'), DatePicker::make('until')->label('Until')])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->where('created_at', '<', now()->parse($date)->addDay()))),
            ])
            ->recordAction('details')
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Activity $record) => $record->action().($record->subject_label ? ': '.$record->subject_label : ''))
                    ->modalContent(fn (Activity $record) => view('filament.activity.history', ['entries' => collect([$record])]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('Nothing recorded yet')
            ->emptyStateDescription('What staff add, change or delete in the admin will be listed here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
