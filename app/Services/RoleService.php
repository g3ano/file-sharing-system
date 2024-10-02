<?php

namespace App\Services;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use TypeError;

class RoleService extends BaseService
{
    /**
     * Gets paginated list of user roles.
     */
    public function getUserRoles(User $user, $page = 1, $limit = 10)
    {
        /**
         * @var LengthAwarePaginator
         */
        $roles = $user->roles()->orderByPivot('created_at', 'desc')->paginate(perPage: $limit, page: $page);
        $roles = $roles->through(function ($item) {
            $item->context = $this->getRoleContext($item);
            return $item;
        });
        return $roles;
    }

    /**
     * Get context at which given role is valid.
     */
    public function getRoleContext(Role $role): ?array
    {
        if (!is_null($role->pivot->workspace_id)) {
            return [
                ResourceEnum::WORKSPACE->value, $role->pivot->workspace_id,
            ];
        } elseif (!is_null($role->pivot->project_id)) {
            return [
                ResourceEnum::PROJECT->value, $role->pivot->project_id,
            ];
        }

        return null;
    }

    /**
     * Checks if user has role.
     *
     * @throws RuntimeException
     */
    public function checkUserRole(User $user, array $data): bool
    {
        try {
            return $user->canDo($this->prepareRoleSearchData($data));
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws RuntimeException
     * @throws TypeError
     */
    public function grantUserRole(User $user, array $data, RoleService $roleService)
    {
        $context = $data['context'];
        $data = $this->prepareRoleDataForDB($user->id, $data);

        $this->isUserRoleExists($data['search']);
        $this->resetUserRolesInContext($roleService, [$user->id], $context);

        $user->roles()->attach($data['roleID'], $data['data']);
    }

    /**
     * @throws RuntimeException
     */
    public function isUserRoleExists(array $data)
    {
        if (
            RoleUser::query()
                ->where($data)
                ->exists()
        ) {
            $this->failedAtRuntime(__('role.grant.exists'), 422);
        }

        return false;
    }

    /**
     * Get resource field name in Pivot table.
     */
    public static function getResourceProperFieldName(?ResourceEnum $resource): string
    {
        return trim(strtolower($resource->name)) . '_id';
    }

    /**
     * Get resource metadata, such as full qualified foreign key,
     * searching array, and data array formatted for DB insert action.
     */
    public static function prepareResourceSearchData(?array $context = null, ?int $roleID = null, int|string|null $userID = null): array
    {
        $result = [];
        $excludedResources = [
            ResourceEnum::USER,
        ];
        [$resource, $resourceID] = $context ?? [null, null];

        if ($resource && !($resource instanceof ResourceEnum)) {
            $resource = ResourceEnum::fromName($resource);
        }

        $result = array_map(
            fn (ResourceEnum $case) => [self::getResourceProperFieldName($case), null],
            array_filter(
                ResourceEnum::cases(),
                fn (ResourceEnum $case) => $case !== $resource && !in_array($case, $excludedResources)
            )
        );

        if ($resource) {
            $result[] = [
                self::getResourceProperFieldName($resource), $resourceID,
            ];
        }

        if ($userID) {
            $result[] = ['user_id', $userID];
        }

        if ($roleID) {
            $result[] = ['role_id', $roleID];
        }

        return $result;
    }

    /**
     * Get a data array for that DB insert action.
     */
    public function prepareResourceInsertData(
        ?array $context = null,
        int|string|null $roleID = null,
        int|string|array|null $userID = null
    ): array {
        $userID = array_filter(is_array($userID) ? $userID : [$userID], fn ($id) => !is_null($id));
        $result = [];
        [$resource, $resourceID] = $context ?? [null, null];

        $resource = !is_null($resource) && !($resource instanceof ResourceEnum)
            ? ResourceEnum::fromName($resource)
            : $resource;

        foreach ($userID as $id) {
            $entry = [
                'user_id' => $id,
            ];

            if ($resource) {
                $entry[$this->getResourceProperFieldName($resource)] = $resourceID ?? null;
            }

            if ($roleID) {
                $entry['role_id'] = $roleID;
            }

            $result[] = $entry;
        }

        return count($result) === 1
            ? $result[0]
            : $result;
    }

    /**
     * Resets users roles within context.
     */
    public function resetUserRolesInContext(RoleService $roleService, array $users = [], ?array $context = null): ?int
    {
        if (empty($users) || !array_is_list($users) || !empty($context) && !array_is_list($context)) {
            return null;
        }

        [$resource, $resourceID] = $context ?? [null, null];

        if (is_array($resourceID)) {
            if (!array_is_list($resourceID)) {
                return null;
            }

            $resource = !($resource instanceof ResourceEnum)
                ? ResourceEnum::fromName($resource)
                : $resource;

            DB::transaction(function () use ($resourceID, $roleService, $users, $resource) {
                return RoleUser::query()
                    ->whereIn(
                        $roleService->getResourceProperFieldName($resource),
                        $resourceID
                    )
                    ->whereIn('user_id', $users)
                    ->delete();
            });
        }

        $resourceMatchingData = $this->prepareResourceSearchData(context: $context);

        return (int) RoleUser::query()
            ->where($resourceMatchingData)
            ->whereIn('user_id', $users)
            ->delete();
    }

    /**
     * Prepare data to use to check Role availability.
     *
     * @throws TypeError
     */
    protected function prepareRoleSearchData(array $data): array
    {
        [
            'role_id' => $roleID,
            'context' => $context,
        ] = $data;

        $role = RoleEnum::from($roleID);
        $resource = ResourceEnum::fromName($context[0] ?? null);
        $resourceID = $context[1] ?? null;

        return [
            [$role, $resource, $resourceID],
        ];
    }

    /**
     * @throws RuntimeException
     * @throws TypeError
     */
    protected function prepareRoleDataForDB(int|string $userID, array $data)
    {
        $result = [];
        [
            'role_id' => $roleID,
            'context' => $context
        ] = $data;

        $result['roleID'] = $roleID;
        $result['data'] = $this->prepareResourceInsertData($context, userID: $userID);
        $result['search'] = $this->prepareResourceSearchData($context, $roleID, $userID);

        return $result;
    }
}
