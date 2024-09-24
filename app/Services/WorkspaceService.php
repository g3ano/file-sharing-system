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
}
