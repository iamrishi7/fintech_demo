<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\Plan::factory(10)->create();
        $this->call([
            RoleSeeder::class,
            // CommissionSeeder::class,
            // UserSeeder::class
        ]);
        // \App\Models\User::factory(10)->create();
    }
}
