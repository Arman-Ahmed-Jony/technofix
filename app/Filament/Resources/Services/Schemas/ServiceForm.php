<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Service;
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

class ServiceForm
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
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                            TextInput::make('slug')
                                ->label('URL')
                                ->prefix(url('services').'/')
                                ->required()
                                ->alphaDash()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            Textarea::make('summary')
                                ->label('Short description')
                                ->helperText('One or two lines, shown on the home page and service cards.')
                                ->required()
                                ->rows(2)
                                ->maxLength(300),
                            RichEditor::make('content')
                                ->label('Details')
                                ->helperText('What you fix / install and how it works. No prices needed — customers call or send a request.')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'link'],
                                    ['h2', 'h3'],
                                    ['bulletList', 'orderedList'],
                                    ['undo', 'redo'],
                                ]),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Display')
                                ->schema([
                                    Toggle::make('is_active')->label('Show on website')->default(true),
                                    Select::make('icon')
                                        ->options(Service::ICONS)
                                        ->default('tool')
                                        ->required()
                                        ->selectablePlaceholder(false)
                                        ->native(false),
                                ]),
                            Section::make('SEO')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('meta_title')->maxLength(70)->helperText('Defaults to the service name.'),
                                    Textarea::make('meta_description')->rows(3)->maxLength(160)->helperText('Defaults to the short description.'),
                                ]),
                        ]),
                ]),
        ]);
    }
}
