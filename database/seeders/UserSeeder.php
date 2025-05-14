<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'capped_balance' => 0
        ])->assignRole('admin');

        User::create([
            'name' => 'Retailer',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'capped_balance' => 100
        ])->assignRole('retailer');
    }
}
