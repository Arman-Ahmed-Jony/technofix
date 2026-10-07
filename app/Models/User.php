<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StaffArea;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Staff account for the control panel (Admin → Settings → Staff). Storefront shoppers live in a separate `customers`
 * table, so every user here is staff. What they can open comes from their role (StaffArea).
 */
#[Fillable(['name', 'email', 'avatar_path', 'password', 'role_id', 'is_active'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    protected $attributes = ['is_active' => true];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    protected static function booted(): void
    {
        // The very first account (e.g. `php artisan make:filament-user` on a new server) is the owner. Later accounts
        // made without a role can't sign in until an owner gives them one.
        static::creating(function (User $user) {
            if ($user->role_id === null && ! self::query()->exists()) {
                $user->role_id = Role::where('is_owner', true)->value('id');
            }
        });

        // A replaced or removed profile photo is deleted from disk.
        static::updated(function (User $user) {
            if ($user->wasChanged('avatar_path') && filled($old = $user->getOriginal('avatar_path'))) {
                Storage::disk('public')->delete($old);
            }
        });

        // Never leave the shop without an active owner.
        static::deleting(function (User $user) {
            if ($user->isOwner() && ! self::otherActiveOwners($user)) {
                throw ValidationException::withMessages(['record' => 'This is the only owner account — make someone else an owner first.']);
            }
        });

        static::updating(function (User $user) {
            $wasOwner = Role::whereKey($user->getOriginal('role_id'))->value('is_owner') && $user->getOriginal('is_active');

            $isOwner = Role::whereKey($user->role_id)->value('is_owner') && $user->is_active;

            if ($wasOwner && ! $isOwner && ! self::otherActiveOwners($user)) {
                throw ValidationException::withMessages(['data.role_id' => 'This is the only owner account — make someone else an owner first.']);
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isOwner(): bool
    {
        return (bool) $this->role?->is_owner;
    }

    public function canUse(StaffArea $area): bool
    {
        return $this->is_active && (bool) $this->role?->allows($area);
    }

    /** Profile photo (My profile / Staff); Filament shows initials when there is none. */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** Staff passwords (the admin can do everything): at least 10 characters with letters and numbers. */
    public static function passwordRule(): Password
    {
        return Password::min(10)->letters()->numbers();
    }

    public const PASSWORD_HINT = 'At least 10 characters, with letters and numbers.';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role_id !== null;
    }

    // ─── Two-step sign-in (authenticator app), managed on My profile ───

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->forceFill(['app_authentication_secret' => $secret])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->forceFill(['app_authentication_recovery_codes' => $codes])->save();
    }

    private static function otherActiveOwners(User $user): bool
    {
        return self::query()->whereKeyNot($user->getKey())->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('is_owner', true))
            ->exists();
    }
}
