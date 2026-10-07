<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Notifications\CustomerResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A storefront shopper (guard "customer"). Signs in with mobile number or email + password. The mobile number is not
 * verified yet (no SMS provider), so orders are only linked to an account when placed while signed in.
 */
#[Fillable(['name', 'phone', 'email', 'password', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    use LogsActivity, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function savedBuilds(): HasMany
    {
        return $this->hasMany(SavedBuild::class)->latest('updated_at')->latest('id');
    }

    public function firstName(): string
    {
        return str($this->name)->trim()->before(' ')->toString();
    }

    /** Only customers with an email can reset their password themselves (by SMS later). */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPassword($token));
    }
}
