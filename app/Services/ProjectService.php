<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

class ProjectService
{
    /**
     * Determine whether a user is member of a project.
     */
    public function userIsProjectMember(User $user, Project $project): bool
    {
        return (bool) once(function () use ($user, $project) {
            return $user->projects()
                ->wherePivot('project_id', $project->id)
                ->exists();
        });
    }
}
