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
    public function before(User $user): ?bool
    {
        if ($user->canDo([
            [RoleEnum::ADMIN],
        ])) {
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
            ])
            ||
            ((new WorkspaceService())->userIsWorkspaceMember($user, $workspace) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
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
            ])
            ||
            ((new ProjectService())->userIsProjectMember($user, $project) &&
                $user->canDo([
                    [RoleEnum::MANAGER, ResourceEnum::PROJECT, $project->id],
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
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
