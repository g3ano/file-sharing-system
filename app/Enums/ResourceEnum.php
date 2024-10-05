<?php

namespace App\Enums;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

enum ResourceEnum: string
{
    case USER = 'user';
    case WORKSPACE = 'workspace';
    case PROJECT = 'project';

    public function class(): string
    {
        return match ($this) {
            ResourceEnum::USER => User::class,
            ResourceEnum::WORKSPACE => Workspace::class,
            ResourceEnum::PROJECT => Project::class,
        };
    }
}
