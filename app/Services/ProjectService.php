<?php

namespace App\Services;

use App\Enums\AbilityEnum;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    /**
     * Determine whether a user is member of a project.
     */
    public function userIsProjectMember(User $user, Project $project): bool
    {
        return (bool) once(function () use ($user, $project) {
            return $user
                ->projects()
                ->wherePivot("project_id", $project->id)
                ->exists();
        });
    }

    /**
     * Determine whether a user is member of a project.
     */
    public function userIsProjectMemberByID(
        User $user,
        int|string $projectID
    ): bool {
        return (bool) once(function () use ($user, $projectID) {
            return $user
                ->projects()
                ->wherePivot("project_id", $projectID)
                ->exists();
        });
    }

    /**
     * Create new project.
     */
    public function createProject(User $user, array $data): Project
    {
        return Project::query()->create([
            "name" => $data["name"],
            "description" => $data["description"],
            "workspace_id" => $data["workspace_id"],
            "user_id" => $user->id,
        ]);
    }

    /**
     * Get paginated list of projects.
     */
    public function getProjectList(
        int $page,
        int $limit,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $projects = Project::query()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $projects;
    }

    /**
     * Search projects.
     */
    public function searchProjectList(
        int $page,
        int $limit,
        string $searchValue,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        $searchValue = "%{$searchValue}%";
        /**
         * @var LengthAwarePaginator
         */
        $projects = Project::query()
            ->whereAny(["name", "description"], "ILIKE", $searchValue)
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $projects;
    }

    /**
     * Get paginated list of deleted projects.
     */
    public function getDeletedProjectList(
        int $page,
        int $limit,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $projects = Project::onlyTrashed()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $projects;
    }

    /**
     * Search deleted projects.
     */
    public function searchDeletedProjectList(
        int $page,
        int $limit,
        string $searchValue,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $projects = Project::onlyTrashed()
            ->whereAny(["name", "description"], "ILIKE", $searchValue)
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $projects;
    }

    /**
     * Get user capabilities for project.
     */
    public function getUserCapabilitiesForProject(
        User $auth,
        Project &$project,
        array $additional = []
    ) {
        $capabilities = [
            AbilityEnum::VIEW->value => $auth->can(
                AbilityEnum::VIEW->value,
                $project
            ),
            AbilityEnum::UPDATE->value => $auth->can(
                AbilityEnum::UPDATE->value,
                $project
            ),
            AbilityEnum::DELETE->value => $auth->can(
                AbilityEnum::DELETE->value,
                $project
            ),
            AbilityEnum::RESTORE->value => $auth->can(
                AbilityEnum::RESTORE->value,
                $project
            ),
            AbilityEnum::FORCE_DELETE->value => $auth->can(
                AbilityEnum::FORCE_DELETE->value,
                $project
            ),
            AbilityEnum::USER_ABILITY_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_MANAGE->value,
                $project
            ),
            AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,
                $project
            ),
            AbilityEnum::PROJECT_MEMBER_LIST->value => $auth->can(
                AbilityEnum::PROJECT_MEMBER_LIST->value,
                $project
            ),
            AbilityEnum::PROJECT_MEMBER_ADD->value => $auth->can(
                AbilityEnum::PROJECT_MEMBER_ADD->value,
                $project
            ),
            AbilityEnum::PROJECT_MEMBER_REMOVE->value => $auth->can(
                AbilityEnum::PROJECT_MEMBER_REMOVE->value,
                $project
            ),
            AbilityEnum::PROJECT_FILES_LIST->value => $auth->can(
                AbilityEnum::PROJECT_FILES_LIST->value,
                $project
            ),
            AbilityEnum::PROJECT_FILES_ADD->value => $auth->can(
                AbilityEnum::PROJECT_FILES_ADD->value,
                $project
            ),
            AbilityEnum::PROJECT_FILES_REMOVE->value => $auth->can(
                AbilityEnum::PROJECT_FILES_REMOVE->value,
                $project
            ),
            ...$additional,
        ];

        $project->capabilities = $capabilities;
    }

    /**
     * Get paginated list of project members.
     */
    public function getProjectMemberList(
        Project $project,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirectin = "asc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $members = $project
            ->members()
            ->orderByPivot($orderByField, $orderByDirectin)
            ->paginate(perPage: $limit, page: $page);

        return $members;
    }

    /**
     * Add members to project.
     */
    public function addProjectMembers(Project $project, array $data)
    {
        $project->members()->attach($data);
    }

    /**
     * Remove members from project.
     */
    public function removeProjectMembers(Project $project, array $data)
    {
        $project->members()->detach($data);
    }
}
