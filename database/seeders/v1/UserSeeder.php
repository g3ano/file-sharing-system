<?php

namespace Database\Seeders\v1;

use App\Enums\AbilityEnum;
use App\Models\User;
use App\Helpers\Slugable;
use App\Models\File;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use Slugable;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->create([
            "first_name" => "Admin",
            "last_name" => "Admin",
            "username" => "admin",
            "email" => "admin@example.com",
            "password" => Hash::make("admin@example.com"),
        ]);
        $manager = User::query()->create([
            "first_name" => "Manager",
            "last_name" => "Manager",
            "username" => "manager",
            "email" => "manager@example.com",
            "password" => Hash::make("manager@example.com"),
        ]);

        BouncerFacade::allow($admin)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::RESTORE->value,
                AbilityEnum::DELETE->value,
                AbilityEnum::FORCE_DELETE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                AbilityEnum::USER_FILE_LIST->value,
            ],
            User::class
        );
        BouncerFacade::allow($admin)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::RESTORE->value,
                AbilityEnum::DELETE->value,
                AbilityEnum::FORCE_DELETE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::STORAGE_VIEW->value,

                AbilityEnum::WORKSPACE_MEMBER_LIST->value,
                AbilityEnum::WORKSPACE_MEMBER_ADD->value,
                AbilityEnum::WORKSPACE_MEMBER_REMOVE->value,
                AbilityEnum::WORKSPACE_PROJECT_LIST->value,
                AbilityEnum::WORKSPACE_PROJECT_ADD->value,
                AbilityEnum::WORKSPACE_PROJECT_REMOVE->value,
            ],
            Workspace::class
        );
        BouncerFacade::allow($admin)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::RESTORE->value,
                AbilityEnum::DELETE->value,
                AbilityEnum::FORCE_DELETE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::STORAGE_VIEW->value,

                AbilityEnum::PROJECT_MEMBER_LIST->value,
                AbilityEnum::PROJECT_MEMBER_ADD->value,
                AbilityEnum::PROJECT_MEMBER_REMOVE->value,
                AbilityEnum::PROJECT_FILES_LIST->value,
                AbilityEnum::PROJECT_FILES_ADD->value,
                AbilityEnum::PROJECT_FILES_REMOVE->value,
            ],
            Project::class
        );
        BouncerFacade::allow($admin)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::DELETE->value,
                AbilityEnum::RESTORE->value,
                AbilityEnum::FORCE_DELETE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::FILE_DOWNLOAD->value,
            ],
            File::class
        );

        BouncerFacade::allow($manager)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,

                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                AbilityEnum::USER_FILE_LIST->value,
            ],
            User::class
        );
        BouncerFacade::forbid($manager)->to(
            [
                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::UPDATE->value,
            ],
            $admin
        );
        BouncerFacade::allow($manager)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::STORAGE_VIEW->value,

                AbilityEnum::WORKSPACE_MEMBER_LIST->value,
                AbilityEnum::WORKSPACE_MEMBER_ADD->value,
                AbilityEnum::WORKSPACE_MEMBER_REMOVE->value,
                AbilityEnum::WORKSPACE_PROJECT_LIST->value,
                AbilityEnum::WORKSPACE_PROJECT_ADD->value,
                AbilityEnum::WORKSPACE_PROJECT_REMOVE->value,
            ],
            Workspace::class
        );
        BouncerFacade::allow($manager)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

                AbilityEnum::STORAGE_VIEW->value,

                AbilityEnum::PROJECT_MEMBER_LIST->value,
                AbilityEnum::PROJECT_MEMBER_ADD->value,
                AbilityEnum::PROJECT_MEMBER_REMOVE->value,
                AbilityEnum::PROJECT_FILES_LIST->value,
                AbilityEnum::PROJECT_FILES_ADD->value,
                AbilityEnum::PROJECT_FILES_REMOVE->value,
            ],
            Project::class
        );
        BouncerFacade::allow($admin)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::DELETE->value,

                AbilityEnum::USER_ABILITY_MANAGE->value,

                AbilityEnum::FILE_DOWNLOAD->value,
            ],
            File::class
        );
    }
}
