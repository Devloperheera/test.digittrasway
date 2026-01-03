<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'),
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'name' => 'GT Connect Admin',
            'email' => '25gtconnect@gmail.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'name' => 'Digit Transway Admin',
            'email' => 'admin@digittransway.com',
            'password' => Hash::make('digit123'),
            'email_verified_at' => now(),
        ]);
    }
}
