<?php

namespace App\Http\Controllers\v1;

use App\Enums\AbilityEnum;
use App\Enums\ProjectMembershipUpdatedActionEnum;
use App\Events\ProjectMembershipUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Project\AddProjectMemberRequest;
use App\Http\Requests\v1\Project\CreateProjectRequest;
use App\Http\Requests\v1\Project\RemoveProjectMembersRequest;
use App\Http\Resources\v1\ProjectCollection;
use App\Http\Resources\v1\ProjectResource;
use App\Http\Resources\v1\UserCollection;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
        $this->orderable = ["id", "createdAt", "name", "description"];
        $this->orderableMap = [
            "createdAt" => "created_at",
        ];
    }

    /**
     * Creates new project.
     */
    public function createProject(CreateProjectRequest $request)
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::CREATE->value, Project::class)) {
            $this->failedAsNotFound("project");
        }

        $data = $request->validated();

        try {
            $createdProject = $this->projectService->createProject(
                $auth,
                $data
            );
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        $this->projectService->getUserCapabilitiesForProject(
            $auth,
            $createdProject
        );

        return new ProjectResource($createdProject);
    }

    /**
     * Deletes a project.
     */
    public function deleteProject(string $projectID)
    {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (!$project || !$auth->can(AbilityEnum::DELETE->value, $project)) {
            $this->failedAsNotFound("project");
        }

        if (!$project->delete()) {
            Log::error("Failed to deleted project", [
                "project" => $project,
            ]);

            $this->failedWithMessage(__("project.deleted.soft"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Force deletes a project.
     */
    public function forceDeleteProject(string $projectID)
    {
        $auth = User::user();
        $project = Project::withTrashed()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::FORCE_DELETE->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        if (!$project->forceDelete()) {
            Log::error("Failed to force deleted project", [
                "project" => $project,
            ]);

            $this->failedWithMessage(__("project.deleted.force_delete"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Force deletes a project.
     */
    public function restoreProject(string $projectID)
    {
        $auth = User::user();
        $project = Project::onlyTrashed()->where("id", $projectID)->first();

        if (!$project || !$auth->can(AbilityEnum::RESTORE->value, $project)) {
            $this->failedAsNotFound("project");
        }

        if (!$project->restore()) {
            Log::error("Failed to restore project", [
                "project" => $project,
            ]);

            $this->failedWithMessage(__("project.deleted.restore"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Get paginated list of projects.
     */
    public function getProjectList(Request $request)
    {
        if ($request->get("searchValue")) {
            return $this->searchProjectList($request);
        }

        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, Project::class)) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $projects = $this->projectService->getProjectList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $projects = $projects->through(function (Project $project) use ($auth) {
            $this->projectService->getUserCapabilitiesForProject(
                $auth,
                $project
            );
            return $project;
        });

        return new ProjectCollection($projects);
    }

    /**
     * Search projects.
     */
    public function searchProjectList(Request $request)
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, Project::class)) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $searchValue = $request->query("searchValue") ?? "";

        $projects = $this->projectService->searchProjectList(
            $page,
            $limit,
            $searchValue,
            $orderByField,
            $orderByDirection
        );

        $projects = $projects->through(function (Project $project) use ($auth) {
            $this->projectService->getUserCapabilitiesForProject(
                $auth,
                $project
            );
            return $project;
        });

        return new ProjectCollection($projects);
    }

    /**
     * Get paginated list of deleted projects.
     */
    public function getDeletedProjectList(Request $request)
    {
        if ($request->get("searchValue")) {
            return $this->searchDeletedProjectList($request);
        }

        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, Project::class)) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $projects = $this->projectService->getDeletedProjectList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $projects = $projects->through(function (Project $project) use ($auth) {
            $this->projectService->getUserCapabilitiesForProject(
                $auth,
                $project
            );
            return $project;
        });

        return new ProjectCollection($projects);
    }

    /**
     * Search deleted projects.
     */
    public function searchDeletedProjectList(Request $request)
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, Project::class)) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $searchValue = $request->query("searchValue") ?? "";

        $projects = $this->projectService->searchDeletedProjectList(
            $page,
            $limit,
            $searchValue,
            $orderByField,
            $orderByDirection
        );

        $projects = $projects->through(function (Project $project) use ($auth) {
            $this->projectService->getUserCapabilitiesForProject(
                $auth,
                $project
            );
            return $project;
        });

        return new ProjectCollection($projects);
    }

    /**
     * Get paginated list of project members.
     */
    public function getProjectMemberList(Request $request, string $projectID)
    {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_MEMBER_LIST->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }
        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $members = $this->projectService->getProjectMemberList(
            $project,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        return new UserCollection($members);
    }

    /**
     * Add members to project.
     */
    public function addProjectMembers(
        AddProjectMemberRequest $request,
        string $projectID
    ) {
        $auth = User::user();
        $project = once(
            fn() => Project::query()->where("id", $projectID)->first()
        );

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_MEMBER_ADD->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        [
            "members" => $members,
        ] = $request->validated();

        try {
            $this->projectService->addProjectMembers($project, $members);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        event(new ProjectMembershipUpdated($members, $project->id));

        return $this->succeedWithStatus();
    }

    /**
     * Remove members from project.
     */
    public function removeProjectMembers(
        RemoveProjectMembersRequest $request,
        string $projectID
    ) {
        $auth = User::user();
        $project = once(
            fn() => Project::query()->where("id", $projectID)->first()
        );

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_MEMBER_REMOVE->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        [
            "members" => $members,
        ] = $request->validated();

        try {
            $this->projectService->removeProjectMembers($project, $members);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        event(
            new ProjectMembershipUpdated(
                $members,
                $project->id,
                ProjectMembershipUpdatedActionEnum::REMOVE
            )
        );

        return $this->succeedWithStatus();
    }

    public function getProjectMemberAbilities(
        string $projectID,
        string $memberID
    ) {
        //
    }
}