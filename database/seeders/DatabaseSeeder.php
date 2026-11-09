<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Initial data only: roles, categories, the owner's seven products (as drafts), draft policy pages and
     * the first administrator (from .env). No demo customers, orders, reviews or stock are created.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            PageSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
