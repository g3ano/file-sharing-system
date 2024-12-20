<?php

namespace App\Http\Controllers\v1;

use App\Enums\AbilityEnum;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StatController extends Controller
{
    /**
     * Get workspace stats.
     */
    public function getWorkspaceStats(string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (!$workspace || !$auth->can(AbilityEnum::VIEW->value, $workspace)) {
            $this->failedAsNotFound("workspace");
        }

        $stats["members"] = [
            "total" => $workspace->members()->withTrashed()->count(),
            "listed" => $workspace->members()->count(),
            "deleted" => $workspace->members()->onlyTrashed()->count(),
        ];
        $stats["projects"] = [
            "total" => $workspace->projects()->withTrashed()->count(),
            "listed" => $workspace->projects()->count(),
            "deleted" => $workspace->projects()->onlyTrashed()->count(),
        ];
        $stats["files"] = [
            "total" => $workspace->files()->withTrashed()->count(),
            "listed" => $workspace->files()->count(),
            "trashed" => $workspace->files()->onlyTrashed()->count(),
        ];

        return $this->succeed($stats, Response::HTTP_OK);
    }

    /**
     * Get project stats.
     */
    public function getProjectStats(string $projectID)
    {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (!$project || !$auth->can(AbilityEnum::VIEW->value, $project)) {
            $this->failedAsNotFound("project");
        }

        $stats["members"] = [
            "total" => $project->members()->withTrashed()->count(),
            "listed" => $project->members()->count(),
            "deleted" => $project->members()->onlyTrashed()->count(),
        ];
        $stats["files"] = [
            "total" => $project->files()->withTrashed()->count(),
            "listed" => $project->files()->count(),
            "trashed" => $project->files()->onlyTrashed()->count(),
        ];

        return $this->succeed($stats, Response::HTTP_OK);
    }
}
