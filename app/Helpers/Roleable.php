<?php

namespace App\Helpers;

use App\Models\User;
use RuntimeException;
use App\Enums\RoleEnum;
use App\Enums\ResourceEnum;
use App\Models\RoleUser;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\Builder;

trait Roleable
{
    use HasResponse;

    /**
     * Determine if user have role or list of roles
     *
     * @throws RuntimeException
     */
    public static function userCanDoByID(int|string $userID, array $data)
    {
        $user = once(fn () => User::query()->where('id', $userID)->first());

        if (!$user) {
            self::failedAtRuntime(__('user.not_found'), 404);
        }

        return self::getUserAbilityFromRolesData($user, $data);
    }

    /**
     * Determine if user have role or list of roles
     *
     * @throws RuntimeException
     */
    public static function userCanDo(User $user, array $data)
    {
        return self::getUserAbilityFromRolesData($user, $data);
    }

    /**
     * Determine if user have role or list of roles
     *
     * @throws RuntimeException
     */
    public function canDo(array $data): bool
    {
        return self::getUserAbilityFromRolesData($this, $data);
    }

    /**
     * Determine user ability using roles data array.
     *
     * @throws RuntimeException
     */
    protected static function getUserAbilityFromRolesData(User $user, array $data): bool
    {
        self::normalizeData($data);

        $roleService = new RoleService();

        return once(fn () => RoleUser::query()
            ->where(function (Builder $query) use ($data, $user, $roleService) {
                foreach ($data as $whereGroup) {
                    $role = array_shift($whereGroup);

                    $query->orWhere(function (Builder $query) use ($whereGroup, $roleService, $role, $user) {
                        $resourceMeta = $roleService->getResourceSearchingData(
                            $whereGroup,
                            $role->value,
                            $user->id,
                        );

                        $query->where($resourceMeta);
                    });
                }
            })->exists());
    }

    /**
     * Normalizes data to a format expected by role checking method.
     *
     * @throws RuntimeException
     */
    protected static function normalizeData(array &$data)
    {
        if (!is_array($data) || empty($data)) {
            self::failedAtRuntime(__('role.roleable.empty'), 422);
        }

        foreach ($data as $key => $whereGroup) {
            $count = count($whereGroup);

            if (!is_array($whereGroup) || empty($whereGroup) || $count > 3) {
                self::failedAtRuntime(__('role.roleable.too_much_data'), 422);
            }

            if ($count !== 3) {
                for ($i = 3 - $count; $i > 0; $i--) {
                    $whereGroup[3 - $i] = null;
                }
            }

            if (
                (!is_null($whereGroup[0]) && !($whereGroup[0] instanceof RoleEnum)) ||
                (!is_null($whereGroup[1]) && !($whereGroup[1] instanceof ResourceEnum)) ||
                (!is_null($whereGroup[2]) && !is_numeric($whereGroup[2]))
            ) {
                self::failedAtRuntime(__('role.roleable.invalid_input'), 422);
            }

            $data[$key] = $whereGroup;
        }
    }
}
