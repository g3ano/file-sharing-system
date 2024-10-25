<?php

namespace App\Services;

use App\Enums\AbilityEnum;
use App\Models\User;
use RuntimeException;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;
use Silber\Bouncer\BouncerFacade;
use Throwable;

class WorkspaceService extends BaseService
{
    /**
     * Determine whether user is member of a workspace.
     */
    public function isUserWorkspaceMember(
        Workspace $workspace,
        User $user
    ): bool {
        return (bool) $user
            ->workspaces()
            ->wherePivot("workspace_id", $workspace->id)
            ->exists();
    }

    /**
     * Determine whether user is member of a workspace.
     */
    public function isUserWorkspaceMemberByID(
        int|string $workspaceID,
        User $user
    ): bool {
        return (bool) once(function () use ($user, $workspaceID) {
            return $user
                ->workspaces()
                ->wherePivot("workspace_id", $workspaceID)
                ->exists();
        });
    }

    /**
     * Creates workspace.
     */
    public function createWorkspace(User $user, array $data): Workspace
    {
        return Workspace::query()->create([
            "name" => $data["name"],
            "description" => $data["description"],
            "slug" => $this->getSlug($data["name"]),
            "user_id" => $user->id,
        ]);
    }

    /**
     * Adds workspace members.
     *
     * @throws RuntimeException
     */
    public function addWorkspaceMembers(Workspace $workspace, array $members)
    {
        if (!array_is_list($members)) {
            $this->failedAtRuntime(
                __("workspace.members.invalid_members_array"),
                422
            );
        }

        $workspace->members()->attach($members);
    }

    /**
     * Removes workspace members.
     *
     * @throws RuntimeException
     */
    public function removeWorkspaceMembers(Workspace $workspace, array $members)
    {
        if (!array_is_list($members)) {
            $this->failedAtRuntime(
                __("workspace.members.invalid_members_array"),
                422
            );
        }

        $workspace->members()->detach($members);
    }

    /**
     * Get paginated list of workspaces, list can be searched.
     */
    public function getWorkspaceList(
        string|int $page,
        string|int $limit,
        string $orderBy = "created_at",
        string $orderByDir = "asc",
        array $includes = []
    ): LengthAwarePaginator {
        return Workspace::query()
            ->with($includes)
            ->select("workspaces.*")
            ->orderBy($orderBy, $orderByDir)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Search workspace list
     */
    public function searchWorkspaceList(
        string $searchValue,
        int $page = 1,
        int $limit = 10,
        string $orderBy = "created_at",
        string $orderByDir = "asc",
        array $includes = []
    ): LengthAwarePaginator {
        $searchValue = "%{$searchValue}%";

        return Workspace::query()
            ->with($includes)
            ->whereAny(["name", "description"], "ILIKE", $searchValue)
            ->orderBy($orderBy, $orderByDir)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Get user joined workspaces.
     */
    public function getUserWorkspaceList(
        User $user,
        int $page = 1,
        int $limit = 10,
        array $includes = [],
        ?string $searchValue = null,
        string $orderBy = "created_at",
        string $orderByDirection = "asc"
    ) {
        $query = $user->workspaces()->with($includes);

        if ($searchValue) {
            $searchValue = "%$searchValue%";
            $query = $query->whereAny(
                ["name", "description"],
                "ILIKE",
                $searchValue
            );
        }

        $result = $query
            ->orderByPivot($orderBy, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $result;
    }

    public function getUserCapabilitiesForWorkspace(
        User $auth,
        Workspace &$workspace,
        array $additional = []
    ) {
        $capabilities = [
            AbilityEnum::VIEW->value => $auth->can(
                AbilityEnum::VIEW->value,
                $workspace
            ),
            AbilityEnum::UPDATE->value => $auth->can(
                AbilityEnum::UPDATE->value,
                $workspace
            ),
            AbilityEnum::DELETE->value => $auth->can(
                AbilityEnum::DELETE->value,
                $workspace
            ),
            AbilityEnum::RESTORE->value => $auth->can(
                AbilityEnum::RESTORE->value,
                $workspace
            ),
            AbilityEnum::FORCE_DELETE->value => $auth->can(
                AbilityEnum::FORCE_DELETE->value,
                $workspace
            ),
            AbilityEnum::USER_ABILITY_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_MANAGE->value,
                $workspace
            ),
            AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_MEMBER_LIST->value => $auth->can(
                AbilityEnum::WORKSPACE_MEMBER_LIST->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_MEMBER_ADD->value => $auth->can(
                AbilityEnum::WORKSPACE_MEMBER_ADD->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_MEMBER_REMOVE->value => $auth->can(
                AbilityEnum::WORKSPACE_MEMBER_REMOVE->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_PROJECT_LIST->value => $auth->can(
                AbilityEnum::WORKSPACE_PROJECT_LIST->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_PROJECT_ADD->value => $auth->can(
                AbilityEnum::WORKSPACE_PROJECT_ADD->value,
                $workspace
            ),
            AbilityEnum::WORKSPACE_PROJECT_REMOVE->value => $auth->can(
                AbilityEnum::WORKSPACE_PROJECT_REMOVE->value,
                $workspace
            ),
            ...$additional,
        ];

        $workspace->capabilities = $capabilities;
    }

    /**
     * Get user capabilities for workspace members.
     */
    public function getUserCapabilitiesForWorkspaceMember(
        User $auth,
        User &$member,
        array $additional = []
    ) {
        $capabilities = [
            AbilityEnum::VIEW->value => $auth->can(
                AbilityEnum::VIEW->value,
                $member
            ),
            AbilityEnum::USER_WORKSPACE_REMOVE->value => $auth->can(
                AbilityEnum::USER_WORKSPACE_REMOVE->value,
                $member
            ),
            ...$additional,
        ];

        $member->capabilities = $capabilities;
    }

    /**
     * Writes state data about workspace member related to workspace.
     */
    public function getWorkspaceMemberState(
        Workspace &$workspace,
        User &$member
    ) {
        $this->isMemberWorkspaceOwner($workspace, $member);
    }

    /**
     * Determine if member is workspace owner.
     */
    public function isMemberWorkspaceOwner(Workspace &$workspace, User &$member)
    {
        $member->isOwner = $workspace->user_id === $member->id;
        $workspace->isOwner = $workspace->user_id === $member->id;
    }

    /**
     * Get workspace member abilities, broad abilities can be included.
     */
    public function getWorkspaceMemberAbilities(
        Workspace $workspace,
        User $user,
        int $page = 1,
        int $limit = 10,
        bool $broad = false
    ): LengthAwarePaginator {
        return $user
            ->prepareAbilitiesBuilderFor($workspace, broad: $broad)
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Update workspace member abilities.
     *
     * @throws Throwable
     */
    public function updateWorkspaceMemberAbilities(
        User $user,
        Workspace $workspace,
        array $data = []
    ) {
        if (empty($data)) {
            return;
        }

        [
            "add" => $abilitiesToAdd,
            "remove" => $abilitiesToRemove,
            "forbid" => $abilitiesToForbid,
        ] = $data;

        if (
            empty($abilitiesToAdd) &&
            empty($abilitiesToRemove) &&
            empty($abilitiesToForbid)
        ) {
            return;
        }

        $abilitiesToAdd = array_diff($abilitiesToAdd, $abilitiesToRemove);
        $abilitiesToAdd = array_diff($abilitiesToAdd, $abilitiesToForbid);
        $abilitiesToRemove = array_diff($abilitiesToRemove, $abilitiesToForbid);

        try {
            //Reset user abilities for given workspace
            foreach (
                [$abilitiesToAdd, $abilitiesToRemove, $abilitiesToForbid]
                as $abilityGroup
            ) {
                BouncerFacade::disallow($user)->to($abilityGroup, $workspace);
                BouncerFacade::unforbid($user)->to($abilityGroup, $workspace);
            }

            if (!empty($abilitiesToAdd)) {
                BouncerFacade::allow($user)->to($abilitiesToAdd, $workspace);
            }

            if (!empty($abilitiesToRemove)) {
                BouncerFacade::disallow($user)->to(
                    $abilitiesToRemove,
                    $workspace
                );
            }

            if (!empty($abilitiesToForbid)) {
                BouncerFacade::forbid($user)->to(
                    $abilitiesToForbid,
                    $workspace
                );
            }
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }
}
