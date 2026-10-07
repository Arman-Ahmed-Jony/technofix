<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'question';

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return Faq::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group', 'group')->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('answer')->required()->rows(4)->columnSpanFull(),
            Select::make('group')
                ->label('Section')
                ->helperText('Questions are grouped by section on the FAQ page. Type a new name to create a section.')
                ->options(fn () => static::groups())
                ->searchable()
                ->createOptionForm([TextInput::make('group')->label('Section name')->required()->maxLength(60)])
                ->createOptionUsing(fn (array $data) => $data['group']),
            Toggle::make('is_active')->label('Show on website')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('question')->description(fn (Faq $record) => str($record->answer)->limit(90))->wrap()->searchable(),
                TextColumn::make('group')->label('Section')->badge()->color('gray')->sortable(),
                ToggleColumn::make('is_active')->label('On website'),
            ])
            ->filters([
                SelectFilter::make('group')->label('Section')->options(fn () => static::groups()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
