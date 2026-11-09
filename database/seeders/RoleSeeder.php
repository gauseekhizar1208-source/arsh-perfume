<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->updateOrCreate(['name' => Role::ADMIN], ['label' => 'Administrator']);
        Role::query()->updateOrCreate(['name' => Role::CUSTOMER], ['label' => 'Customer']);
    }
}
