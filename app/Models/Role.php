<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';

    public const CUSTOMER = 'customer';

    protected $fillable = ['name', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Role id by name (e.g. Role::idFor(Role::CUSTOMER)). Throws if the roles were never seeded. */
    public static function idFor(string $name): int
    {
        return (int) static::query()->where('name', $name)->firstOrFail()->id;
    }
}
