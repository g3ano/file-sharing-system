<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Workspace;
use App\Enums\AbilityEnum;
use App\Enums\ResourceEnum;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Silber\Bouncer\BouncerFacade;

class UserService extends BaseService
{
    public function searchForUsers(
        string $searchValue,
        int $page = 1,
        int $limit = 10,
        string $orderBy = "created_at",
        string $orderByDir = "asc",
        array $includes = []
    ): LengthAwarePaginator {
        $searchValue = "%{$searchValue}%";

        return User::query()
            ->with($includes)
            ->whereAny(
                ["first_name", "last_name", "email", "username"],
                "ILIKE",
                $searchValue
            )
            ->orderBy($orderBy, $orderByDir)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Gets authenticated user capabilities to a target user.
     */
    public function getUserCapabilitiesForUser(
        ?User $auth = null,
        ?User &$target = null,
        bool $isAuth = false
    ): void {
        if (!$target || !$auth) {
            return;
        }

        $capabilities = [];

        if ($isAuth) {
            $capabilities = [
                "users list" => $auth->can(
                    AbilityEnum::LIST->value,
                    User::class
                ),
                "users create" => $auth->can(
                    AbilityEnum::CREATE->value,
                    User::class
                ),
                "workspaces list" => $auth->can(
                    AbilityEnum::LIST->value,
                    Workspace::class
                ),
                "workspaces create" => $auth->can(
                    AbilityEnum::CREATE->value,
                    Workspace::class
                ),
                "projects list" => $auth->can(
                    AbilityEnum::LIST->value,
                    Project::class
                ),
                "projects list" => $auth->can(
                    AbilityEnum::CREATE->value,
                    Project::class
                ),
            ];
        } else {
            $capabilities = [
                AbilityEnum::VIEW->value => $auth->can(
                    AbilityEnum::VIEW->value,
                    $target
                ),
                AbilityEnum::UPDATE->value => $auth->can(
                    AbilityEnum::UPDATE->value,
                    $target
                ),
                AbilityEnum::DELETE->value => $auth->can(
                    AbilityEnum::DELETE->value,
                    $target
                ),
                AbilityEnum::RESTORE->value => $auth->can(
                    AbilityEnum::RESTORE->value,
                    $target
                ),
                AbilityEnum::FORCE_DELETE->value => $auth->can(
                    AbilityEnum::FORCE_DELETE->value,
                    $target
                ),
                AbilityEnum::USER_ABILITY_VIEW->value => $auth->can(
                    AbilityEnum::USER_ABILITY_VIEW->value,
                    $target
                ),
                AbilityEnum::USER_ABILITY_MANAGE->value =>
                    ($auth->can(
                        AbilityEnum::USER_ABILITY_MANAGE->value,
                        $target
                    ) &&
                        !$target->can("*", "*")) ||
                    ($target->can("*", "*") && $auth->can("*", "*")),
                AbilityEnum::USER_WORKSPACE_LIST->value => $auth->can(
                    AbilityEnum::USER_WORKSPACE_LIST->value,
                    $target
                ),
                AbilityEnum::USER_WORKSPACE_ADD->value => $auth->can(
                    AbilityEnum::USER_WORKSPACE_ADD->value,
                    $target
                ),
                AbilityEnum::USER_WORKSPACE_REMOVE->value => $auth->can(
                    AbilityEnum::USER_WORKSPACE_REMOVE->value,
                    $target
                ),
                AbilityEnum::USER_PROJECT_LIST->value => $auth->can(
                    AbilityEnum::USER_PROJECT_LIST->value,
                    $target
                ),
                AbilityEnum::USER_PROJECT_ADD->value => $auth->can(
                    AbilityEnum::USER_PROJECT_ADD->value,
                    $target
                ),
                AbilityEnum::USER_PROJECT_REMOVE->value => $auth->can(
                    AbilityEnum::USER_PROJECT_REMOVE->value,
                    $target
                ),
            ];
        }

        $target->capabilities = $capabilities;
    }

    /**
     * Assign proper abilities to newly registered user.
     */
    public function assignRegisteredUserAbilities(
        User $registeredUser,
        string|array $additional = []
    ): void {
        $additional = (array) $additional;

        BouncerFacade::allow($registeredUser)->to(
            [
                AbilityEnum::VIEW->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::DELETE->value,

                AbilityEnum::USER_ABILITY_VIEW->value,
                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                ...$additional,
            ],
            $registeredUser
        );
        BouncerFacade::allow($registeredUser)->to(
            [
                AbilityEnum::VIEW->value,

                AbilityEnum::USER_ABILITY_VIEW->value,
                ...$additional,
            ],
            User::class
        );
    }

    /**
     * Gets paginated list of users.
     */
    public function getUserList(
        int $page = 1,
        int $limit = 10,
        string $orderBy = "created_at",
        string $orderByDirection = "asc"
    ): LengthAwarePaginator {
        return User::query()
            ->orderBy($orderBy, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Gets paginated list of deleted users.
     */
    public function getUserDeletedList(
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        return User::onlyTrashed()
            ->orderBy("created_at", "desc")
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Adds workspaces to user.
     *
     * @throws RuntimeException
     */
    public function addUserWorkspaces(User $user, array $workspaces): void
    {
        if (!array_is_list($workspaces)) {
            $this->failedAtRuntime(__("workspace.members.workspaces"), 422);
        }

        $user->workspaces()->attach($workspaces);
    }

    /**
     * Removes workspaces to user.
     *
     * @throws RuntimeException
     */
    public function removeUserWorkspaces(User $user, array $workspaces): void
    {
        if (!array_is_list($workspaces)) {
            $this->failedAtRuntime(__("workspace.members.workspaces"), 422);
        }

        $user->workspaces()->detach($workspaces);
    }

    /**
     * Gets paginated list of user abilities.
     */
    public function getUserAbilities(
        User $user,
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        return $user
            ->abilities()
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page)
            ->through(function ($item) {
                $this->getUserAbilityContext($item);
                return $item;
            });
    }

    /**
     * Gets paginated list of user global abilities.
     */
    public function getUserGlobalAbilities(
        User $user,
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        /**
         * @var LengthAwarePaginator
         */
        $users = $user
            ->prepareAbilitiesBuilderFor()
            ->whereNull("entity_id")
            ->paginate(perPage: $limit, page: $page);

        $users = $users->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $users;
    }

    /**
     * Gets paginated list of user workspaces abilities.
     */
    public function getUserWorkspaceAbilities(
        User $user,
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        /**
         * @var LengthAwarePaginator
         */
        $users = $user
            ->prepareAbilitiesBuilderFor()
            ->whereNull("entity_id")
            ->paginate(perPage: $limit, page: $page);

        $users = $users->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $users;
    }

    /**
     * Update user global abilities, i.e: whole class and all instances level.
     */
    public function updateUserGlobalAbilities(User $user, array $data): void
    {
        $this->formatUpdateUserGlobalAbilitiesData($data);

        [
            "add" => $abilitiesToAdd,
            "remove" => $abilitiesToRemove,
        ] = $data;

        if (empty($abilitiesToAdd) && empty($abilitiesToRemove)) {
            return;
        }

        $this->handleAddGlobalAbilitiesToUser($user, $abilitiesToAdd);
        $this->handleRemoveGlobalAbilitiesFromUser($user, $abilitiesToRemove);
    }

    /**
     * Update user abilities.
     */
    public function updateUserAbilities(
        User $user,
        User $target,
        array $data
    ): void {
        if (empty($data)) {
            return;
        }

        [
            "add" => $abilitiesToAdd,
            "remove" => $abilitiesToRemove,
        ] = $data;

        if (empty($abilitiesToAdd) && empty($abilitiesToRemove)) {
            return;
        }

        $abilitiesToAdd = array_diff($abilitiesToAdd, $abilitiesToRemove);

        BouncerFacade::allow($user)->to($abilitiesToAdd, $target);
        BouncerFacade::disallow($user)->to($abilitiesToRemove, $target);
    }

    /**
     * Format update user abilities data array.
     */
    protected function formatUpdateUserGlobalAbilitiesData(
        array &$abilitiesData
    ): void {
        $abilitiesData = array_map(function (array $abilityGroup) {
            return $this->formatUpdateUserGlobalAbilitiesDataGroup(
                $abilityGroup
            );
        }, $abilitiesData);

        $this->removeDuplicateAbilitiesFromData($abilitiesData);
    }

    /**
     * Remove duplicate abilities from update user global abilities
     * data groups.
     * @param array<int,mixed> $data
     */
    protected function removeDuplicateAbilitiesFromData(array &$data): void
    {
        foreach ($data["add"] as $type => $abilities) {
            if (array_key_exists($type, $data["remove"])) {
                $data["add"][$type] = array_diff(
                    $abilities,
                    $data["remove"][$type]
                );
            }
        }
    }

    /**
     * Returns ability-type (resolved into corresponding class name)
     * keyed array with unique ability names array as values.
     */
    protected function formatUpdateUserGlobalAbilitiesDataGroup(
        array $abilityGroup
    ): array {
        $entityTypes = array_unique(array_column($abilityGroup, "type"));
        $formattedAbilities = [];

        foreach ($entityTypes as $entityType) {
            $abilitiesByType = array_filter(
                $abilityGroup,
                fn($entity) => $entity["type"] === $entityType
            );
            $uniqueAbilitiesNamesByType = array_values(
                array_unique(array_column($abilitiesByType, "name"))
            );

            $formattedAbilities[
                ResourceEnum::from($entityType)->class()
            ] = $uniqueAbilitiesNamesByType;
        }

        return $formattedAbilities;
    }

    /**
     * Handle user abilities update addition action.
     */
    protected function handleAddGlobalAbilitiesToUser(
        User $user,
        array $abilitiesToAdd
    ): void {
        foreach ($abilitiesToAdd as $type => $abilityNames) {
            if (!empty($abilityNames)) {
                BouncerFacade::allow($user)->to($abilityNames, $type);
            }
        }
    }

    /**
     * Handle user abilities update removal action.
     */
    protected function handleRemoveGlobalAbilitiesFromUser(
        User $user,
        array $abilitiesToRemove
    ): void {
        foreach ($abilitiesToRemove as $type => $abilityNames) {
            if (!empty($abilityNames)) {
                BouncerFacade::disallow($user)->to($abilityNames, $type);
            }
        }
    }
}
