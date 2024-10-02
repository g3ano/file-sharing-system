<?php

namespace App\Policies;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->canDo([
            [RoleEnum::ADMIN],
        ])) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any workspace.
     */
    public function viewAnyWorkspace(User $user): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the workspace.
     */
    public function viewWorkspace(User $user, Workspace $workspace): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
            [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
            [RoleEnum::EDITOR, ResourceEnum::WORKSPACE, $workspace->id],
            [RoleEnum::VIEWER, ResourceEnum::WORKSPACE, $workspace->id],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can view workspace members.
     */
    public function viewWorkspaceMembers(User $user, Workspace $workspace): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
            [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
            [RoleEnum::EDITOR, ResourceEnum::WORKSPACE, $workspace->id],
            [RoleEnum::VIEWER, ResourceEnum::WORKSPACE, $workspace->id],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can add members to workspace.
     */
    public function addWorkspaceMembers(User $user, Workspace $workspace): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can add another user to list of workspaces.
     */
    public function addUserToWorkspaces(User $user): bool
    {
        if ($user->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can remove members from workspace.
     */
    public function removeWorkspaceMembers(User $user, Workspace $workspace): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
            [RoleEnum::MANAGER, ResourceEnum::WORKSPACE, $workspace->id],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create workspace.
     */
    public function createWorkspace(User $user): bool
    {
        if ($user->canDo([
            [RoleEnum::MANAGER],
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function updateWorkspace(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function deleteWorkspace(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the workspace.
     */
    public function restoreWorkspace(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the workspace.
     */
    public function forceDeleteWorkspace(User $user, Workspace $workspace): bool
    {
        return false;
    }
}
