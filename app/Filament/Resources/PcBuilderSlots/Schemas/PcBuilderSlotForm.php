<?php

namespace App\Filament\Resources\PcBuilderSlots\Schemas;

use App\Enums\BuilderRole;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\PcBuilderSlot;
use App\Models\ProductSpec;
use App\Support\IconSprite;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PcBuilderSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Slot')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->required()->maxLength(60)->placeholder('e.g. Processor'),
                    TextInput::make('description')
                        ->label('Hint')
                        ->maxLength(160)
                        ->placeholder('e.g. The brain of your PC')
                        ->helperText('Shown under the name on the builder page.'),
                    TextInput::make('icon')
                        ->maxLength(50)
                        ->helperText(new HtmlString('A <a href="https://tabler.io/icons" target="_blank" rel="noopener" class="underline">Tabler icon</a> name, e.g. <code>cpu</code>.'))
                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (filled($value) && ! IconSprite::exists($value)) {
                                $fail('There is no Tabler icon with this name.');
                            }
                        }]),
                    Select::make('section')->options(PcBuilderSlot::SECTIONS)->default('core')->required()->selectablePlaceholder(false),
                    TextInput::make('max_qty')
                        ->label('How many can be added')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(1)
                        ->required()
                        ->helperText('1 for most parts; e.g. 4 for RAM sticks or 3 for monitors.'),
                    Toggle::make('is_required')->label('Needed for a complete PC')->inline(false),
                    Toggle::make('is_active')->label('Show on the builder')->default(true)->inline(false),
                ]),

            Section::make('Products')
                ->description('Which products customers can pick for this slot.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('category_ids')
                        ->label('Categories')
                        ->multiple()
                        ->options(fn () => Category::treeOptions())
                        ->searchable()
                        ->required()
                        ->helperText('Sub-categories are included.')
                        ->columnSpanFull(),
                    Select::make('filter_attribute_id')
                        ->label('Only products whose…')
                        ->options(fn () => Attribute::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('No filter')
                        ->live()
                        ->helperText('Optional, e.g. Form Factor → Desktop (DIMM) keeps laptop RAM out.'),
                    TagsInput::make('filter_values')
                        ->label('…is one of')
                        ->placeholder('Add a value and press Enter')
                        ->suggestions(fn (Get $get) => $get('filter_attribute_id')
                            ? ProductSpec::where('attribute_id', $get('filter_attribute_id'))->distinct()->orderBy('value')->limit(100)->pluck('value')->all()
                            : [])
                        ->required(fn (Get $get) => filled($get('filter_attribute_id')))
                        ->visible(fn (Get $get) => filled($get('filter_attribute_id'))),
                ]),

            Section::make('Compatibility')
                ->columnSpanFull()
                ->schema([
                    Select::make('role')
                        ->label('Check this slot as')
                        ->options(BuilderRole::class)
                        ->placeholder('Not checked (storage, monitor, keyboard…)')
                        ->unique(ignoreRecord: true)
                        ->validationMessages(['unique' => 'Another slot is already checked as this part.'])
                        ->helperText('The checks (socket, memory type, size, GPU length, power) compare the parts in these slots — turn them on/off in Catalog → PC Builder rules. One slot per type.'),
                ]),
        ]);
    }
}
