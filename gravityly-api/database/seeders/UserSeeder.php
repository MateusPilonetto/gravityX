<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Demo User',
            'username' => 'demo_user',
            'email' => 'demo@gravityly.test',
            'password' => 'secret123',
            'bio' => 'Just trying out Gravityly!',
        ]);
    }
}
