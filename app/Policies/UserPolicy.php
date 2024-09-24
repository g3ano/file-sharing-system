<?php

namespace App\Policies;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectService;
use App\Services\WorkspaceService;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (
            $ability !== 'deleteUser' &&
            $user->canDo([
                [RoleEnum::ADMIN],
            ])
        ) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAnyUser(User $user): bool
    {
        return $user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ]);
    }

    /**
     * Determine whether the user can view any workspace user.
     */
    public function viewAnyWorkspaceUser(User $user, Workspace $workspace): bool
    {
        if (
            $user->canDo([
                [RoleEnum::MANAGER],
                [RoleEnum::VIEWER],
            ])
            ||
            ((new WorkspaceService())->userIsWorkspaceMember($user, $workspace) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
                    [RoleEnum::EDITOR, ResourceEnum::WORKSPACE, $workspace->id],
                    [RoleEnum::VIEWER, ResourceEnum::WORKSPACE, $workspace->id],
                ]))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view any project user.
     */
    public function viewAnyProjectUser(User $user, Project $project): bool
    {
        if (
            $user->canDo([
                [RoleEnum::MANAGER],
                [RoleEnum::VIEWER],
            ])
            ||
            ((new ProjectService())->userIsProjectMember($user, $project) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::PROJECT, $project->id],
                    [RoleEnum::EDITOR, ResourceEnum::PROJECT, $project->id],
                    [RoleEnum::VIEWER, ResourceEnum::PROJECT, $project->id],
                ]))
            ||
            ((new WorkspaceService())->userIsWorkspaceMemberByID($user, $project->workspace_id) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $project->workspace_id],
                    [RoleEnum::EDITOR, ResourceEnum::WORKSPACE, $project->workspace_id],
                    [RoleEnum::VIEWER, ResourceEnum::WORKSPACE, $project->workspace_id],
                ]))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewUser(User $user, User $target): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function createUser(User $user): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update own data.
     */
    public function updateUser(User $user): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the another user.
     */
    public function deleteUser(User $user, User $target): bool
    {
        $isTargetAdminOrManager = $target->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
        ]);

        if (!$isTargetAdminOrManager) {
            return $user->canDo([
                [RoleEnum::ADMIN],
                [RoleEnum::MANAGER],
            ]);
        }

        if ($user->canDo([
            [RoleEnum::ADMIN],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restoreUser(User $user, User $target): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDeleteUser(User $user, User $model): bool
    {
        return false;
    }
}
