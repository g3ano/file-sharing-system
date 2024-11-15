<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Workspace;
use App\Enums\AbilityEnum;
use App\Enums\ResourceEnum;
use App\Models\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    ) {
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
                ResourceEnum::FILE->value => [
                    AbilityEnum::LIST->value => $auth->can(
                        AbilityEnum::LIST->value,
                        File::class
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
                AbilityEnum::USER_PROJECT_LIST->value => $auth->is($target)
                    ? true
                    : $auth->can(
                        AbilityEnum::USER_PROJECT_LIST->value,
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
    ) {
        $additional = (array) $additional;

        BouncerFacade::allow($registeredUser)->to(
            [
                AbilityEnum::VIEW->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::DELETE->value,

                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                AbilityEnum::USER_FILE_LIST->value,
                ...$additional,
            ],
            $registeredUser
        );
        BouncerFacade::allow($registeredUser)->to(
            [AbilityEnum::VIEW->value, ...$additional],
            User::class
        );
        BouncerFacade::allow($registeredUser)->to(
            [
                AbilityEnum::VIEW->value,
                AbilityEnum::UPDATE->value,
                AbilityEnum::DELETE->value,

                AbilityEnum::FILE_DOWNLOAD->value,
                ...$additional,
            ],
            File::class
        );
    }

    /**
     * Gets paginated list of users.
     */
    public function getUserList(
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "asc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $users = User::query()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $users;
    }

    public function searchUserList(
        string $searchValue,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "asc"
    ) {
        $searchValue = "%{$searchValue}%";

        /**
         * @var LengthAwarePaginator
         */
        $users = User::query()
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
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $users;
    }

    /**
     * Gets paginated list of deleted users.
     */
    public function getUserDeletedList(
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "asc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $users = User::onlyTrashed()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $users;
    }

    /**
     * Search deleted user list.
     */
    public function searchDeletedUserList(
        string $searchValue,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "asc"
    ) {
        $searchValue = "%{$searchValue}%";

        /**
         * @var LengthAwarePaginator
         */
        $users = User::onlyTrashed()
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
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $users;
    }

    /**
     * Gets paginated list of user abilities.
     */
    public function getUserAbilities(User $user, int $page = 1, int $limit = 10)
    {
        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor()
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($ability) {
            $this->getUserAbilityContext($ability);

            return $ability;
        });

        return $abilities;
    }

    public function searchUserAbilities(
        User $user,
        string $searchValue = "",
        int $page = 1,
        int $limit = 10
    ) {
        $searchValue = "%{$searchValue}%";
        $abilities = Models::table("abilities");

        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor()
            ->with("abilitable")
            ->whereAny(
                [
                    "{$abilities}.name",
                    "{$abilities}.title",
                    "{$abilities}.entity_type",
                ],
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
        ?string $scope = null
    ) {
        $scope = ResourceEnum::tryFrom($scope)
            ? ResourceEnum::tryFrom($scope)->class()
            : $scope;

        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor($scope, true)
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $abilities;
    }

    /**
     * Gets paginated list of user abilities against another user.
     */
    public function getUserAbilitiesForUser(
        User $user,
        User $target,
        int $page = 1,
        int $limit = 10
    ) {
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
    public function updateUserGlobalAbilities(User $user, array $data)
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
     * Update user.
     */
    public function updateUser(User $user, array $data)
    {
        if (array_key_exists("password", $data)) {
            $data["password"] = Hash::make($data["password"]);
        }

        return $user->update([
            ...$data,
            "first_name" => ucfirst($data["first_name"]),
            "last_name" => ucfirst($data["last_name"]),
        ]);
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
    ) {
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
     */
    protected function removeDuplicateAbilitiesGlobalFromData(array &$data)
    {
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
    ) {
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
     * Handle user global abilities update addition action.
     *
     * @throws Throwable
     */
    protected function handleAddGlobalAbilitiesToUser(
        User $user,
        array $abilitiesToAdd
    ) {
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
    ) {
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
    ) {
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
