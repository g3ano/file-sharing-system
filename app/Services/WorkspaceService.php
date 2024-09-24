<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;

class WorkspaceService
{
    /**
     * Determine whether a user is member of a workspace.
     */
    public function userIsWorkspaceMember(User $user, Workspace $workspace): bool
    {
        return (bool) $user->workspaces()
            ->wherePivot('workspace_id', $workspace->id)
            ->exists();
    }

    /**
     * Determine whether a user is member of a workspace.
     */
    public function userIsWorkspaceMemberByID(User $user, int|string $workspaceID): bool
    {
        return (bool) once(function () use ($user, $workspaceID) {
            return $user->workspaces()
             ->wherePivot('workspace_id', $workspaceID)
             ->exists();
        });
    }
}
