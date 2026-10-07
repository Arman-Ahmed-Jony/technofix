<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make()
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                            TextInput::make('slug')
                                ->label('URL')
                                ->prefix(url('page').'/')
                                ->required()
                                ->alphaDash()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            RichEditor::make('content')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'link'],
                                    ['h2', 'h3'],
                                    ['bulletList', 'orderedList', 'blockquote'],
                                    ['table'],
                                    ['undo', 'redo'],
                                ])
                                ->columnSpanFull(),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Publishing')
                                ->schema([
                                    Toggle::make('is_published')->label('Published')->default(true),
                                    Select::make('footer_group')
                                        ->label('Show in footer column')
                                        ->options(Page::FOOTER_GROUPS)
                                        ->placeholder('Not in footer')
                                        ->native(false),
                                    Textarea::make('excerpt')
                                        ->label('Short summary')
                                        ->helperText('Shown under the page title.')
                                        ->rows(3)
                                        ->maxLength(300),
                                ]),
                            Section::make('SEO')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('meta_title')->maxLength(70)->helperText('Defaults to the page title.'),
                                    Textarea::make('meta_description')->rows(3)->maxLength(160)->helperText('Defaults to the short summary.'),
                                ]),
                        ]),
                ]),
        ]);
    }
}
