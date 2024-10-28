<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class StorageService extends BaseService
{
    /**
     * Gets used space for workspace.
     */
    public function getWorkspaceUsedSpace(Workspace $workspace)
    {
        $space = DB::table("workspaces")
            ->join("projects", "workspaces.id", "=", "projects.workspace_id")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("workspaces.id", "=", $workspace->id)
            ->where("workspaces.deleted_at", "=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for workspace.
     */
    public function getDeletedWorkspaceUsedSpace(Workspace $workspace)
    {
        $space = DB::table("workspaces")
            ->join("projects", "workspaces.id", "=", "projects.workspace_id")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("workspaces.id", "=", $workspace->id)
            ->where("workspaces.deleted_at", "!=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for all workspaces.
     */
    public function getWorkspaceListUsedSpace()
    {
        $space = DB::table("workspaces")
            ->join("projects", "workspaces.id", "=", "projects.workspace_id")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("workspaces.deleted_at", "=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for all deleted workspaces.
     */
    public function getDeletedWorkspaceListUsedSpace()
    {
        $space = DB::table("workspaces")
            ->join("projects", "workspaces.id", "=", "projects.workspace_id")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("workspaces.deleted_at", "!=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for project.
     */
    public function getProjectUsedSpace(Project $project)
    {
        $space = DB::table("projects")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("projects.id", "=", $project->id)
            ->where("projects.deleted_at", "=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for deleted project.
     */
    public function getDeletedProjectUsedSpace(Project $project)
    {
        $space = DB::table("projects")
            ->join("files", "projects.id", "=", "files.project_id")
            ->where("projects.id", "=", $project->id)
            ->where("projects.deleted_at", "!=", null)
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Gets used space for all projects.
     */
    public function getProjectListTotalUsedSpace()
    {
        $space = DB::table("projects")
            ->join("files", "projects.id", "=", "files.project_id")
            ->select(DB::raw("SUM(files.size) as files_size"))
            ->first();

        return $space?->files_size;
    }

    /**
     * Returns information about disk space for given directory.
     *
     * @throws RuntimeException
     */
    public function getDiskSpace($directory = "/")
    {
        $data = [];

        try {
            $data["free"] = disk_free_space($directory);
            $data["total"] = disk_total_space($directory);
            $data["used"] =
                disk_total_space($directory) - disk_free_space($directory);
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }

        return $data;
    }
}
