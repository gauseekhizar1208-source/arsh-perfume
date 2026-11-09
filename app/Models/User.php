<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * SECURITY: role_id and is_active are intentionally NOT mass assignable. No request payload can ever
     * promote an account to administrator. Roles are assigned only by code (registration => customer,
     * AdminUserSeeder / `arsh:create-admin` => admin).
     */
    protected $fillable = ['name', 'email', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token', 'google_id'];
    protected $attributes = ['password_login_enabled' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'password_login_enabled' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === Role::ADMIN;
    }

    public function scopeCustomers($query)
    {
        return $query->whereHas('role', fn ($q) => $q->where('name', Role::CUSTOMER));
    }

    /** Lifetime purchase total (same revenue definition as the dashboard: paid, not cancelled/returned, net of refunds). */
    public function lifetimeTotal(): string
    {
        $paise = $this->orders()->revenue()->get(['grand_total', 'refunded_amount'])
            ->sum(fn ($o) => Money::toPaise($o->grand_total) - Money::toPaise($o->refunded_amount));

        return Money::fromPaise((int) $paise);
    }
}
