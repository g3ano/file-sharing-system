<?php

namespace Database\Seeders;

use App\Helpers\Slugable;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\Role;
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
        foreach (RoleEnum::cases() as $case) {
            Role::query()->create([
                'name' => $case->name,
                'slug' => $this->getSlug($case->name),
            ]);
        }

        Workspace::factory(10)->create();
        Project::factory(10)->create();

        $superAdmin = User::query()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'super_admin',
            'slug' => $this->getSlug('super_admin'),
            'email' => 'super.admin@example.com',
            'password' => Hash::make('super.admin@example.com'),
        ]);

        $superAdmin->roles()->attach([
            RoleEnum::MANAGER->value => [
                'workspace_id' => 1,
            ],
            RoleEnum::EDITOR->value => [
                'workspace_id' => 2,
            ],
        ]);
        $superAdmin->roles()->attach([
            RoleEnum::MANAGER->value => [
                'project_id' => 1,
            ],
            RoleEnum::EDITOR->value => [
                'project_id' => 2,
            ],
        ]);
        $superAdmin->roles()->attach(RoleEnum::ADMIN->value);

        User::factory(20)->create();
    }
}
