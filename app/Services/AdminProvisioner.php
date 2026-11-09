<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use InvalidArgumentException;

/**
 * The ONLY code path that creates administrators (CLI command + seeder). There is no public admin
 * registration route and role_id is not mass assignable.
 */
class AdminProvisioner
{
    public const MIN_PASSWORD_LENGTH = 12;

    public function createOrPromote(string $name, string $email, string $password): User
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid administrator email address is required.');
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('The administrator password must be at least '.self::MIN_PASSWORD_LENGTH.' characters.');
        }

        $user = User::query()->where('email', strtolower($email))->first() ?? new User;
        $user->name = $name !== '' ? $name : 'Administrator';
        $user->email = strtolower($email);
        $user->password = $password;          // hashed by the model cast
        $user->role_id = Role::idFor(Role::ADMIN);
        $user->is_active = true;
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
