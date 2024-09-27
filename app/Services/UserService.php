<?php

namespace App\Services;

use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Get users list depending on authenticated user role.
     */
    public function getUserListByRole(User $user, int $page = 1, int $limit = 10, string $orderBy = 'created_at')
    {
        return $this->getUserListQueryBuilder($user)
            ->orderBy($orderBy, 'desc')
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Builds proper query to constrain user list depending on authenticated
     * user role.
     */
    public function getUserListQueryBuilder(User $user): EloquentBuilder
    {
        $query = null;

        //Return all users if user is one of following roles
        if ($user->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ])) {
            return User::query();
        }

        //Else if user is a manager of any workspace
        //return users that are members of any of those workspaces
        //that are managed by him
        if ($user->isAnyWorkspaceManager()) {
            $workspaceUsers = $this->getUsersManagerBy($user, ResourceEnum::WORKSPACE);
            $query = User::query()->whereIn('id', $workspaceUsers);
        }

        //Else if user is a manager of any project
        //return users that are members of any of those projects
        //that are managed by him
        if ($user->isAnyProjectManager()) {
            $projectUsers = $this->getUsersManagerBy($user, ResourceEnum::PROJECT);

            $query = $query
                ? $query
                    ->orWhereIn('id', $projectUsers)
                    ->distinct()
                : User::query()->whereIn('id', $projectUsers);
        }

        //Finally, return the query or an empty query
        return $query ?: User::query()->whereRaw('1 = 0');
    }

    public function searchForUsers(User $user, string $searchValue, int $page = 1, int $limit = 10)
    {
        $searchValue = "%{$searchValue}%";

        return $this->getUserListQueryBuilder($user)
            ->whereAny([
                'first_name',
                'last_name',
                'email',
                'username',
            ], 'ILIKE', $searchValue)
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Get builder query that get users related to user through a resource.
     */
    protected function getUsersManagerBy(User $user, ResourceEnum $resource): ?Builder
    {
        switch ($resource) {
            case ResourceEnum::WORKSPACE:
                $table = 'user_workspace';
                $column = 'workspace_id';
                break;
            case ResourceEnum::PROJECT:
                $table = 'project_user';
                $column = 'project_id';
                break;

            default:
                $table = null;
                $column = null;
                break;
        }

        if (!$table || !$column) {
            return null;
        }

        return DB::table($table)
            ->select('user_id')
            ->whereIn($column, function (Builder $query) use ($user, $table, $column) {
                $query->select("{$table}.{$column}")
                    ->from($table)
                    ->where("{$table}.user_id", $user->id)
                    ->join('role_user', "{$table}.{$column}", "role_user.{$column}")
                    ->where('role_user.role_id', RoleEnum::MANAGER);
            });
    }
}
