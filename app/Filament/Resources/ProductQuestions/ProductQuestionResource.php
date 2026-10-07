<?php

namespace App\Filament\Resources\ProductQuestions;

use App\Enums\QuestionStatus;
use App\Filament\Resources\ProductQuestions\Pages\EditProductQuestion;
use App\Filament\Resources\ProductQuestions\Pages\ListProductQuestions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductQuestion;
use App\Services\ProductFeedback;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Customer questions: write the answer and it appears on the product page (the customer gets an email). */
class ProductQuestionResource extends Resource
{
    protected static ?string $model = ProductQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Questions';

    protected static ?string $modelLabel = 'question';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'questions';

    public static function getNavigationBadge(): ?string
    {
        $waiting = ProductQuestion::where('status', QuestionStatus::Pending)->count();

        return $waiting ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Questions waiting for an answer';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('The question')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('product.name')->label('Product')
                        ->url(fn (ProductQuestion $record) => $record->product ? ProductResource::getUrl('edit', ['record' => $record->product]) : null),
                    TextEntry::make('customer.name')->label('Customer')
                        ->state(fn (ProductQuestion $record) => trim(($record->customer?->name ?? '—').' · '.($record->customer?->phone ?? '')))
                        ->helperText(fn (ProductQuestion $record) => 'Shown on the site as “'.$record->authorName().'”'),
                    TextEntry::make('question')->columnSpanFull()->extraAttributes(['class' => 'whitespace-pre-line']),
                ]),
            Section::make('Your answer')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('answer')->hiddenLabel()->rows(4)->maxLength(2000)
                        ->required(fn ($get) => (bool) $get('publish'))
                        ->placeholder('Write a clear, friendly answer…'),
                    Toggle::make('publish')->label('Show the question and answer on the product page')->default(true)
                        ->helperText('The first time you publish an answer, the customer gets an email (if they have an email address).'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['product:id,name,slug', 'customer:id,name,phone']))
            ->columns([
                TextColumn::make('created_at')->label('Asked')->since()->dateTimeTooltip('d M Y, h:i A')->sortable(),
                TextColumn::make('question')->wrap()->lineClamp(2)->tooltip(fn (ProductQuestion $record) => $record->question)->searchable()->weight('medium'),
                TextColumn::make('product.name')->label('Product')->wrap()->lineClamp(2)->color('gray')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->description(fn (ProductQuestion $record) => $record->customer?->phone)->searchable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->recordActions([
                Action::make('answer')->label('Answer')->icon('heroicon-o-chat-bubble-left-right')->color('primary')
                    ->visible(fn (ProductQuestion $record) => $record->status === QuestionStatus::Pending)
                    ->modalHeading('Answer the question')
                    ->modalDescription(fn (ProductQuestion $record) => '“'.$record->question.'” — '.$record->product?->name)
                    ->schema([Textarea::make('answer')->required()->rows(4)->maxLength(2000)])
                    ->modalSubmitActionLabel('Publish answer')
                    ->action(fn (ProductQuestion $record, array $data) => app(ProductFeedback::class)->answer($record, $data['answer'], auth('web')->user())),
                ActionGroup::make([
                    EditAction::make()->label('Open / edit'),
                    Action::make('hide')->label('Hide')->icon('heroicon-o-eye-slash')->color('gray')
                        ->visible(fn (ProductQuestion $record) => $record->status !== QuestionStatus::Hidden)
                        ->action(fn (ProductQuestion $record) => app(ProductFeedback::class)->hideQuestion($record)),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('No questions here')
            ->emptyStateDescription('Questions customers ask on product pages appear here for you to answer.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductQuestions::route('/'),
            'edit' => EditProductQuestion::route('/{record}/edit'),
        ];
    }
}
