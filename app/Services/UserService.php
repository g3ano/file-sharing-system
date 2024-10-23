<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Workspace;
use App\Enums\AbilityEnum;
use App\Enums\ResourceEnum;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Models;
use Throwable;

class UserService extends BaseService
{
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
                ResourceEnum::USER->value => [
                    AbilityEnum::LIST->value => $auth->can(
                        AbilityEnum::LIST->value,
                        User::class
                    ),
                    AbilityEnum::CREATE->value => $auth->can(
                        AbilityEnum::CREATE->value,
                        User::class
                    ),
                    AbilityEnum::USER_ABILITY_MANAGE->value => $auth->can(
                        AbilityEnum::USER_ABILITY_MANAGE->value,
                        User::class
                    ),
                    AbilityEnum::USER_ABILITY_SPECIAL_MANAGE
                        ->value => $auth->can(
                        AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,
                        User::class
                    ),
                ],
                ResourceEnum::WORKSPACE->value => [
                    AbilityEnum::LIST->value => $auth->can(
                        AbilityEnum::LIST->value,
                        Workspace::class
                    ),
                    AbilityEnum::CREATE->value => $auth->can(
                        AbilityEnum::CREATE->value,
                        Workspace::class
                    ),
                ],
                ResourceEnum::PROJECT->value => [
                    AbilityEnum::LIST->value => $auth->can(
                        AbilityEnum::LIST->value,
                        Project::class
                    ),
                    AbilityEnum::CREATE->value => $auth->can(
                        AbilityEnum::CREATE->value,
                        Project::class
                    ),
                ],
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
                AbilityEnum::USER_ABILITY_MANAGE->value => $auth->can(
                    AbilityEnum::USER_ABILITY_MANAGE->value,
                    $target
                ),
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value => $auth->can(
                    AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,
                    $target
                ),
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
                AbilityEnum::USER_PROJECT_LIST->value => $auth->is($target)
                    ? true
                    : $auth->can(
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

                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                ...$additional,
            ],
            $registeredUser
        );
        BouncerFacade::allow($registeredUser)->to(
            [AbilityEnum::VIEW->value, ...$additional],
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

    public function searchUserList(
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
                [
                    "first_name",
                    "last_name",
                    "email",
                    "username",
                    DB::raw("CONCAT(first_name, ' ', last_name)"),
                ],
                "ILIKE",
                $searchValue
            )
            ->orderBy($orderBy, $orderByDir)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Gets paginated list of deleted users.
     */
    public function getUserDeletedList(
        int $page = 1,
        int $limit = 10,
        string $orderBy = "created_at",
        string $orderByDirection = "asc"
    ): LengthAwarePaginator {
        return User::onlyTrashed()
            ->orderBy($orderBy, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Search deleted user list.
     */
    public function searchDeletedUserList(
        string $searchValue,
        int $page = 1,
        int $limit = 10,
        string $orderBy = "created_at",
        string $orderByDir = "asc",
        array $includes = []
    ): LengthAwarePaginator {
        $searchValue = "%{$searchValue}%";

        return User::onlyTrashed()
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
        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor()
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $abilities;
    }

    public function searchUserAbilities(
        User $user,
        string $searchValue,
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        $searchValue = "%{$searchValue}%";
        $abilities = Models::table("abilities");

        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor()
            ->with("abilitable")
            ->whereAny(
                ["{$abilities}.name", "{$abilities}.title", "{$abilities}.entity_type"],
                "ILIKE",
                $searchValue
            )
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $abilities;
    }

    /**
     * Gets paginated list of user global abilities.
     */
    public function getUserGlobalAbilities(
        User $user,
        int $page = 1,
        int $limit = 10,
        ?string $type = null
    ): LengthAwarePaginator {
        $type = ResourceEnum::tryFrom($type)
            ? ResourceEnum::tryFrom($type)->class()
            : $type;

        /**
         * @var LengthAwarePaginator
         */
        $users = $user
            ->prepareAbilitiesBuilderFor($type, true)
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $users = $users->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $users;
    }

    /**
     * Gets paginated list of user abilities against another user.
     */
    public function getUserAbilitiesForUser(
        User $user,
        User $target,
        int $page = 1,
        int $limit = 10
    ): LengthAwarePaginator {
        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor($target)
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $abilities;
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
            "forbid" => $abilitiesToForbid,
        ] = $data;

        if (
            empty($abilitiesToAdd) &&
            empty($abilitiesToRemove) &&
            empty($abilitiesToForbid)
        ) {
            return;
        }

        $this->handleAddGlobalAbilitiesToUser($user, $abilitiesToAdd);
        $this->handleRemoveGlobalAbilitiesFromUser($user, $abilitiesToRemove);
        $this->handleForbidGlobalAbilitiesFromUser($user, $abilitiesToForbid);
    }

    /**
     * Update user abilities for another user.
     */
    public function updateUserAbilitiesForUser(
        User $user,
        User $target,
        array $data
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
            //Reset user given abilities for given user
            foreach (
                [$abilitiesToAdd, $abilitiesToRemove, $abilitiesToForbid]
                as $abilityGroup
            ) {
                BouncerFacade::disallow($user)->to($abilityGroup, $target);
                BouncerFacade::unforbid($user)->to($abilityGroup, $target);
            }

            if (!empty($abilitiesToAdd)) {
                BouncerFacade::allow($user)->to($abilitiesToAdd, $target);
            }

            if (!empty($abilitiesToRemove)) {
                BouncerFacade::disallow($user)->to($abilitiesToRemove, $target);
            }

            if (!empty($abilitiesToForbid)) {
                BouncerFacade::forbid($user)->to($abilitiesToForbid, $target);
            }
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
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

        $this->removeDuplicateAbilitiesGlobalFromData($abilitiesData);
    }

    /**
     * Remove duplicate abilities from update user global abilities
     * data groups.
     *
     * @param array<int,mixed> $data
     */
    protected function removeDuplicateAbilitiesGlobalFromData(
        array &$data
    ): void {
        //Removes any duplicate abilities between `add`, `remove`
        //and `forbid` groups from `add` group
        foreach ($data["add"] as $type => $abilities) {
            if (array_key_exists($type, $data["remove"])) {
                $data["add"][$type] = array_diff(
                    $abilities,
                    $data["remove"][$type]
                );
            }
            if (array_key_exists($type, $data["forbid"])) {
                $data["add"][$type] = array_diff(
                    $abilities,
                    $data["forbid"][$type]
                );
            }
        }

        //Removes any duplicate abilities between `remove`
        //and `forbid` groups from `remove` group
        foreach ($data["remove"] as $type => $abilities) {
            if (array_key_exists($type, $data["forbid"])) {
                $data["remove"][$type] = array_diff(
                    $abilities,
                    $data["forbid"][$type]
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

            $formattedAbilities[ResourceEnum::from($entityType)->class()] = $uniqueAbilitiesNamesByType;
        }

        return $formattedAbilities;
    }

    /**
     * Handle user global abilities update addition action.
     *
     * @throws Throwable
     */
    protected function handleAddGlobalAbilitiesToUser(
        User $user,
        array $abilitiesToAdd
    ): void {
        if (empty($abilitiesToAdd)) {
            return;
        }

        foreach ($abilitiesToAdd as $type => $abilityNames) {
            if (!empty($abilityNames)) {
                try {
                    DB::beginTransaction();
                    BouncerFacade::unforbid($user)->to($abilityNames, $type);
                    BouncerFacade::disallow($user)->to($abilityNames, $type);
                    BouncerFacade::allow($user)->to($abilityNames, $type);
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    $this->failedAtRuntime($e->getMessage(), $e->getCode());
                }
            }
        }
    }

    /**
     * Handle user global abilities update removal action.
     */
    protected function handleRemoveGlobalAbilitiesFromUser(
        User $user,
        array $abilitiesToRemove
    ): void {
        if (empty($abilitiesToRemove)) {
            return;
        }

        foreach ($abilitiesToRemove as $type => $abilityNames) {
            if (!empty($abilityNames)) {
                try {
                    DB::beginTransaction();
                    BouncerFacade::unforbid($user)->to($abilityNames, $type);
                    BouncerFacade::disallow($user)->to($abilityNames, $type);
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    $this->failedAtRuntime($e->getMessage(), $e->getCode());
                }
            }
        }
    }

    /**
     * Handle user global abilities update forbid action.
     */
    protected function handleForbidGlobalAbilitiesFromUser(
        User $user,
        array $abilitiesToForbid
    ): void {
        if (empty($abilitiesToForbid)) {
            return;
        }

        foreach ($abilitiesToForbid as $type => $abilityNames) {
            if (!empty($abilityNames)) {
                try {
                    DB::beginTransaction();
                    BouncerFacade::unforbid($user)->to($abilityNames, $type);
                    BouncerFacade::disallow($user)->to($abilityNames, $type);
                    BouncerFacade::forbid($user)->to($abilityNames, $type);
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    $this->failedAtRuntime($e->getMessage(), $e->getCode());
                }
            }
        }
    }
}
