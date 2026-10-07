<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\BrandSettings;
use App\Support\Brand;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** The shop's logos and icon, used on the website, in the admin, on invoices, in emails and PDF quotes. */
class ManageBranding extends SettingsPage
{
    protected static string $settings = BrandSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?string $title = 'Logo & icon';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'branding';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'Upload your own files to replace the built-in TechnoFix ones. Leave a box empty to keep the current file.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Logos')
                ->columns(['default' => 1, 'md' => 2])
                ->columnSpanFull()
                ->schema([
                    self::upload('logo', 'Logo — for light backgrounds', ['image/svg+xml', 'image/png', 'image/webp'], 'Header in light mode, admin, invoices. SVG is sharpest; or a PNG/WebP about 600 × 120 px with a transparent background.', Brand::logo(), false),
                    self::upload('logo_dark', 'Logo — for dark backgrounds', ['image/svg+xml', 'image/png', 'image/webp'], 'Dark mode, the footer and the maintenance page: light-coloured text.', Brand::logoDark(), true),
                ]),
            Section::make('Icon & email logo')
                ->columns(['default' => 1, 'md' => 2])
                ->columnSpanFull()
                ->schema([
                    self::upload('icon', 'Site icon (favicon)', ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'], 'Square, at least 512 × 512 px PNG. Shown on the browser tab, on phones’ home screens and to Google.', Brand::icon(), false),
                    self::upload('email_logo', 'Logo for emails & PDF quotes', ['image/png', 'image/jpeg'], 'PNG or JPG about 440 × 84 px on a white or transparent background — email apps can’t show SVG.', Brand::emailLogo(), false),
                ]),
        ]);
    }

    /**
     * @param  list<string>  $types
     */
    protected static function upload(string $name, string $label, array $types, string $help, string $current, bool $dark): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory('brand')
            ->visibility('public')
            ->acceptedFileTypes($types)
            ->maxSize(2048)
            ->imagePreviewHeight('90')
            ->helperText(new HtmlString(e($help).'<span class="mt-2 flex items-center gap-3"><span class="text-xs">Now:</span><span class="inline-flex rounded-lg px-3 py-2 ring-1 ring-gray-950/10 '.($dark ? 'bg-[#031019]' : 'bg-white').'"><img src="'.e($current).'" alt="" class="h-7 w-auto"></span></span>'));
    }
}
