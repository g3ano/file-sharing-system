<?php

namespace App\Policies;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectService;
use App\Services\WorkspaceService;

class RolePolicy
{
    /**
     * Handler that runs before any other check.
     */
    public function before(User $user): ?bool
    {
        if ($user->canDo([
            [RoleEnum::ADMIN],
        ])) {
            return true;
        }

        return null;
    }

    public function grantUserGlobalRole(User $user, int|string $roleID): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
        ]) && (int) $roleID !== RoleEnum::ADMIN->value) {
            return true;
        }

        return false;
    }

    public function grantUserWorkspaceRole(User $user, Workspace $workspace, User $target, string|int $roleID): bool
    {
        $workspaceService = (new WorkspaceService());

        if (
            !$workspaceService->userIsWorkspaceMember($target, $workspace)
        ) {
            return false;
        }

        if (
            (int) $roleID === RoleEnum::MANAGER->value &&
            !$user->canDo([
                [RoleEnum::MANAGER],
            ])
        ) {
            return false;
        }

        if (
            ($user->canDo([
                [RoleEnum::MANAGER],
            ]))
            ||
            ($workspaceService->userIsWorkspaceMember($user, $workspace)) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
                ])
        ) {
            return true;
        }

        return false;
    }

    public function grantUserProjectRole(User $user, Project $project, User $target): bool
    {
        $projectService = (new ProjectService());

        if (
            !$projectService->userIsProjectMember($target, $project)
        ) {
            return false;
        }

        if (
            ($user->canDo([
                [RoleEnum::MANAGER],
            ]))
            ||
            ($projectService->userIsProjectMember($user, $project)) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::PROJECT, $project->id],
                ])
        ) {
            return true;
        }

        return false;
    }

    public function viewUserRoles(User $user): bool
    {
        return true;
    }

    public function viewAuthUserRoles(User $user): bool
    {
        return true;
    }
}
