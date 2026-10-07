<?php

namespace App\Filament\Resources\ProductReviews;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviews\Pages\EditProductReview;
use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductReview;
use App\Services\ProductFeedback;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Customer reviews: publish (they appear on the product page), hide, or reply publicly. */
class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $modelLabel = 'review';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'reviews';

    public static function getNavigationBadge(): ?string
    {
        $waiting = ProductReview::where('status', ReviewStatus::Pending)->count();

        return $waiting ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Reviews waiting for approval';
    }

    public static function stars(int $rating): string
    {
        return str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('The review')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('product.name')->label('Product')
                        ->url(fn (ProductReview $record) => $record->product ? ProductResource::getUrl('edit', ['record' => $record->product]) : null),
                    TextEntry::make('customer.name')->label('Customer')
                        ->state(fn (ProductReview $record) => trim(($record->customer?->name ?? '—').' · '.($record->customer?->phone ?? '')))
                        ->helperText(fn (ProductReview $record) => 'Shown on the site as “'.$record->authorName().'”'),
                    TextEntry::make('rating')->label('Rating')
                        ->state(fn (ProductReview $record) => self::stars($record->rating).'  ('.$record->rating.'/5)')
                        ->color('warning'),
                    TextEntry::make('verified')->label('Verified purchase')
                        ->state(fn (ProductReview $record) => $record->order ? 'Yes — order '.$record->order->number : 'No (not bought from us)'),
                    TextEntry::make('title')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('body')->label('Review')->columnSpanFull()->extraAttributes(['class' => 'whitespace-pre-line']),
                ]),
            Section::make('Your decision')
                ->columnSpanFull()
                ->schema([
                    Select::make('status')->options(ReviewStatus::class)->required()->selectablePlaceholder(false)
                        ->helperText('Only published reviews appear on the product page and count towards its star rating.'),
                    Textarea::make('reply')->label('Public reply from the shop (optional)')->rows(3)->maxLength(1000)
                        ->helperText('Shown under the review, e.g. to thank the customer or answer a complaint.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['product:id,name,slug', 'customer:id,name,phone']))
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->dateTimeTooltip('d M Y, h:i A')->sortable(),
                TextColumn::make('rating')->label('Stars')->formatStateUsing(fn (int $state) => self::stars($state))->color('warning')->sortable(),
                TextColumn::make('product.name')->label('Product')->wrap()->lineClamp(2)->color('gray')->searchable(),
                TextColumn::make('body')->label('Review')
                    ->state(fn (ProductReview $record) => $record->title ? $record->title.' — '.$record->body : $record->body)
                    ->wrap()->lineClamp(2)->tooltip(fn (ProductReview $record) => $record->body)->searchable(['title', 'body']),
                TextColumn::make('customer.name')->label('Customer')->description(fn (ProductReview $record) => $record->customer?->phone)->searchable(),
                IconColumn::make('order_id')->label('Bought')->boolean()->state(fn (ProductReview $record) => $record->order_id !== null)
                    ->tooltip(fn (ProductReview $record) => $record->order_id ? 'Verified purchase' : 'Not bought from us'),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']),
            ])
            ->recordActions([
                Action::make('approve')->label('Publish')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (ProductReview $record) => $record->status !== ReviewStatus::Approved)
                    ->action(fn (ProductReview $record) => app(ProductFeedback::class)->approve($record)),
                ActionGroup::make([
                    EditAction::make()->label('Open / reply'),
                    Action::make('hide')->label('Hide')->icon('heroicon-o-eye-slash')->color('gray')
                        ->visible(fn (ProductReview $record) => $record->status !== ReviewStatus::Rejected)
                        ->action(fn (ProductReview $record) => app(ProductFeedback::class)->hide($record)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->label('Publish selected')->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each(fn (ProductReview $review) => app(ProductFeedback::class)->approve($review))),
                    BulkAction::make('hide')->label('Hide selected')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each(fn (ProductReview $review) => app(ProductFeedback::class)->hide($review))),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No reviews here')
            ->emptyStateDescription('Reviews customers write on product pages appear here for you to approve.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'edit' => EditProductReview::route('/{record}/edit'),
        ];
    }
}
