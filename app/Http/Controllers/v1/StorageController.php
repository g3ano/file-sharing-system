<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class StorageController extends Controller
{
    protected $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Gets workspace list used storage space.
     */
    public function getWorkspaceListUsedSpace()
    {
        $space = $this->storageService->getWorkspaceListUsedSpace();

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Gets workspace used storage space.
     */
    public function getWorkspaceUsedSpace(string $workspaceID)
    {
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (!$workspace) {
            $this->failedAsNotFound("workspace");
        }

        $space = $this->storageService->getWorkspaceUsedSpace($workspace);

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Gets deleted workspace list used storage space.
     */
    public function getDeletedWorkspaceListUsedSpace()
    {
        $space = $this->storageService->getDeletedWorkspaceListUsedSpace();

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Gets workspace used storage space.
     */
    public function getDeletedWorkspaceUsedSpace(string $workspaceID)
    {
        $workspace = Workspace::onlyTrashed()
            ->where("id", $workspaceID)
            ->first();

        if (!$workspace) {
            $this->failedAsNotFound("workspace");
        }

        $space = $this->storageService->getDeletedWorkspaceUsedSpace(
            $workspace
        );

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Gets workspace used storage space.
     */
    public function getProjectUsedSpace(string $projectID)
    {
        $project = Project::query()->where("id", $projectID)->first();

        if (!$project) {
            $this->failedAsNotFound("project");
        }

        $space = $this->storageService->getProjectUsedSpace($project);

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Gets workspace used storage space.
     */
    public function getDeletedProjectUsedSpace(string $projectID)
    {
        $project = Project::onlyTrashed()->where("id", $projectID)->first();

        if (!$project) {
            $this->failedAsNotFound("project");
        }

        $space = $this->storageService->getDeletedProjectUsedSpace($project);

        return $this->succeed(
            ["data" => $space ?? 0],
            Response::HTTP_OK,
            false
        );
    }

    public function getDiskSpaceData()
    {
        try {
            $data = $this->storageService->getDiskSpace();
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        return $this->succeed(["data" => $data], Response::HTTP_OK, false);
    }
}
