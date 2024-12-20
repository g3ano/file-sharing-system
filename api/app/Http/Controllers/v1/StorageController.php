<?php

namespace App\Http\Controllers\v1;

use App\Enums\AbilityEnum;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
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
        $auth = User::user();

        if (!$auth->can(AbilityEnum::STORAGE_VIEW->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

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
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::STORAGE_VIEW->value, $workspace)
        ) {
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
        $auth = User::user();

        if (!$auth->can(AbilityEnum::STORAGE_VIEW->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

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
        $auth = User::user();
        $workspace = Workspace::onlyTrashed()
            ->where("id", $workspaceID)
            ->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::STORAGE_VIEW->value, $workspace)
        ) {
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
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::STORAGE_VIEW->value, $project)
        ) {
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
        $auth = User::user();
        $project = Project::onlyTrashed()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::STORAGE_VIEW->value, $project)
        ) {
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
        $auth = User::user();

        if (!$auth->can(AbilityEnum::STORAGE_VIEW->value)) {
            $this->failedAsNotFound("user");
        }

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
