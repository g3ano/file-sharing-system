<?php

namespace Database\Seeders;

use App\Helpers\Slugable;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use Slugable;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'super_admin',
            'slug' => $this->getSlug('super_admin'),
            'email' => 'super.admin@example.com',
            'password' => Hash::make('super.admin@example.com'),
        ]);
    }
}
