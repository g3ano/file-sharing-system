<?php

namespace App\Services;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Support\Collection;
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
        $roles = $user->roles()->paginate(perPage: $limit, page: $page);
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
            return $user->canDo($this->formatRoleCheckingData($data));
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws RuntimeException
     * @throws TypeError
     */
    public function grantUserRole(User $user, array $data)
    {
        $context = $data['context'];
        $data = $this->prepareRoleDataForDB($user->id, $data);

        $this->isUserRoleExists($data);
        $this->userResetContextRoles([$user->id], $context);

        $user->roles()->attach($data['roleID'], $data['data']);
    }

    /**
     * @throws RuntimeException
     */
    public function isUserRoleExists(array $data)
    {
        if (
            RoleUser::query()
                ->where($data['search'])
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
     * Get resource metadata, such as full qualified foreign key
     * and searching proper array.
     */
    public static function getResourceSearchingData(?array $context = null, ?int $roleID = null, int|string|null $userID = null): array
    {
        $result = [];
        $excludedResources = [
            ResourceEnum::USER,
        ];
        [$resource, $resourceID] = $context ?? [null, null];

        if (!is_null($resource) && !($resource instanceof ResourceEnum)) {
            $resource = ResourceEnum::fromName($resource);
        }

        $result = Collection::make(ResourceEnum::cases())
            ->filter(
                function (ResourceEnum $case) use ($resource, $excludedResources) {
                    return $case !== $resource &&
                        !in_array($case, $excludedResources);
                }
            )
            ->map(function (ResourceEnum $case) {
                return self::getResourceProperFieldName($case);
            })
            ->map(function (string $resourceName) {
                return [$resourceName, null];
            })
            ->all();

        if (!is_null($resource)) {
            $result[] = [
                self::getResourceProperFieldName($resource), $resourceID,
            ];
        }

        if (!is_null($userID)) {
            $result[] = ['user_id', $userID];
        }

        if (!is_null($roleID)) {
            $result[] = ['role_id', $roleID];
        }

        return $result;
    }

    /**
     * Resets users roles within context.
     */
    public function userResetContextRoles(array $users, ?array $context = null): int
    {
        if (!array_is_list($users) || !empty($context) && !array_is_list($context)) {
            return 0;
        }

        $resourceMatchingData = $this->getResourceSearchingData(context: $context);

        return (int) RoleUser::query()
            ->where($resourceMatchingData)
            ->whereIn('user_id', $users)
            ->delete();
    }

    public function getResourceDBData(
        ?array $context = null,
        int|string|null $roleID = null,
        int|string|array|null $userID = null
    ): array {
        $userID = is_array($userID) ? $userID : [$userID];
        $result = [];
        [$resource, $resourceID] = $context ?? [null, null];

        $resource = !is_null($resource) && !($resource instanceof ResourceEnum)
            ? ResourceEnum::fromName($resource)
            : $resource;

        foreach ($userID as $id) {
            if (is_null($id)) {
                continue;
            }

            $entry = [
                'user_id' => $id,
            ];

            if (!is_null($resource)) {
                $entry[$this->getResourceProperFieldName($resource)] = $resourceID ?? null;
            }

            if (!is_null($roleID)) {
                $entry['role_id'] = $roleID;
            }

            $result[] = $entry;
        }

        return count($result) === 1
            ? $result[0]
            : $result;
    }

    /**
     * Prepare data to use to check Role availability.
     *
     * @throws TypeError
     */
    protected function formatRoleCheckingData(array $data): array
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
        $result['data'] = $this->getResourceDBData($context, userID: $userID);
        $result['search'] = $this->getResourceSearchingData($context, $roleID, $userID);

        return $result;
    }

    /**
     * Get list of data expected by QueryBuilder.
     */
}
