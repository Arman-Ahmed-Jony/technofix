<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * My profile (user menu in the sidebar): every staff member's own photo, name, email, password and two-step sign-in.
 */
class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'My profile';

    public static function getLabel(): string
    {
        return 'My profile';
    }

    public function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = $this->getUser();

        return $schema
            ->inlineLabel(false)
            ->components([
                Section::make('Your details')
                    ->description('Your photo and name show next to what you do in the admin.')
                    ->columns(['default' => 1, 'md' => 4])
                    ->schema([
                        StaffResource::avatarField()->columnSpan(['md' => 1]),
                        Grid::make(['default' => 1, 'md' => 2])
                            ->columnSpan(['md' => 3])
                            ->schema([
                                $this->getNameFormComponent(),
                                $this->getEmailFormComponent()->label('Email (to sign in)'),
                                Text::make(new HtmlString('Role: <b>'.e($user->role?->name ?? '—').'</b> — '.e(implode(', ', $user->role?->areaLabels() ?? []))
                                    .($user->isOwner() ? '' : '. Ask an owner if you need more.')))
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Change password')
                    ->description(User::PASSWORD_HINT.' Leave empty to keep your current password.')
                    ->columns(2)
                    ->schema([
                        $this->getPasswordFormComponent()->rule(User::passwordRule()),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent()->columnSpanFull(),
                    ]),
            ]);
    }
}
