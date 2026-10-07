<?php

namespace App\Filament\Schemas;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryBrandPage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

/**
 * Form + table for category + brand pages (/laptop/dell), used from both the Brand and the Category edit screens.
 * $pick is the side chosen in the form: 'category' on a brand, 'brand' on a category.
 */
class CategoryBrandPageSchema
{
    public static function form(Schema $schema, string $pick): Schema
    {
        $owner = $pick === 'category' ? 'brand_id' : 'category_id';

        return $schema->columns(1)->components([
            Select::make("{$pick}_id")
                ->label(Str::headline($pick))
                ->options(fn () => $pick === 'category' ? Category::treeOptions() : Brand::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required()
                ->unique(
                    CategoryBrandPage::class,
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, RelationManager $livewire) => $rule->where($owner, $livewire->getOwnerRecord()->getKey()),
                ),
            Section::make('Buying guide')
                ->description('Empty text or no questions = the defaults from Content → Buying guide. '.BuyingGuideFields::NAME_HINT)
                ->schema([
                    RichEditor::make('content')
                        ->label('Text')
                        ->toolbarButtons([['bold', 'italic', 'link'], ['h3'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
                    BuyingGuideFields::faqs(),
                ]),
            Section::make('SEO')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')->maxLength(70)->helperText('Empty = the template in Settings → SEO & sharing.'),
                    Textarea::make('meta_description')->rows(3)->maxLength(160)->helperText('Empty = the template in Settings → SEO & sharing.'),
                ]),
        ]);
    }

    public static function table(Table $table, string $pick): Table
    {
        return $table
            ->heading($pick === 'category' ? 'Category pages' : 'Brand pages')
            ->description('Own buying-guide text, questions and SEO for pages like “/laptop/dell”. Pages without an entry here use the defaults (Content → Buying guide).')
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'brand']))
            ->columns([
                TextColumn::make("{$pick}.name")->label(Str::headline($pick))->weight('medium'),
                TextColumn::make('content')
                    ->label('Text')
                    ->formatStateUsing(fn (?string $state) => Str::limit(strip_tags((string) $state), 60))
                    ->placeholder('Default')
                    ->color('gray'),
                TextColumn::make('faqs')
                    ->label('Questions')
                    ->state(fn (CategoryBrandPage $record) => count($record->faqs ?? []) ?: 'Default')
                    ->color('gray'),
                TextColumn::make('updated_at')->label('Updated')->since(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add page')->modalWidth('3xl'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (CategoryBrandPage $record) => $record->url(), shouldOpenInNewTab: true),
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No custom pages yet')
            ->emptyStateDescription('All '.($pick === 'category' ? 'category' : 'brand').' pages use the default buying guide.');
    }
}
