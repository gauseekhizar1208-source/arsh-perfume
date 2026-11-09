<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;

class UserAddressPolicy
{
    public function update(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, UserAddress $address): bool
    {
        return $address->user_id === $user->id;
    }
}
