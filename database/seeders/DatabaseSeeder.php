<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Admin account
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'ho_ten' => 'Administrator'
        ]);
    }
}
