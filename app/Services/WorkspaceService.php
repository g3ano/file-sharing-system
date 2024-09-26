<?php

namespace App\Services;

use Throwable;
use App\Models\User;
use RuntimeException;
use App\Models\Workspace;
use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\RoleUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WorkspaceService extends BaseService
{
    /**
     * Determine whether a user is member of a workspace.
     */
    public function userIsWorkspaceMember(User $user, Workspace $workspace): bool
    {
        return (bool) $user->workspaces()
            ->wherePivot('workspace_id', $workspace->id)
            ->exists();
    }

    /**
     * Determine whether a user is member of a workspace.
     */
    public function userIsWorkspaceMemberByID(User $user, int|string $workspaceID): bool
    {
        return (bool) once(function () use ($user, $workspaceID) {
            return $user->workspaces()
             ->wherePivot('workspace_id', $workspaceID)
             ->exists();
        });
    }

    public function createWorkspace(User $user, array $data): Workspace
    {
        return Workspace::query()
            ->create([
                'name' => $data['name'],
                'description' => $data['description'],
                'slug' => $this->getSlug($data['name']),
                'user_id' => $user->id,
            ]);
    }

    /**
     * @throws RuntimeException
     * @throws Throwable
     */
    public function addWorkspaceMembers(Workspace $workspace, array $data)
    {
        [
            'members' => $members,
        ] = $data;

        if (!array_is_list($members)) {
            $this->failedAtRuntime(__('workspace.members.invalid_members_array'), 422);
        }

        try {
            DB::beginTransaction();

            $workspace->members()->attach($members);

            $this->syncWorkspaceMemberRolesAfterRemoval($workspace, $members);
            $this->syncWorkspaceMemberRolesAfterAddition($workspace, $members);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws RuntimeException
     */
    public function removeWorkspaceMembers(Workspace $workspace, array $data)
    {
        [
            'members' => $members,
        ] = $data;

        if (!array_is_list($members)) {
            $this->failedAtRuntime(__('workspace.members.invalid_members_array'), 422);
        }

        try {
            DB::beginTransaction();

            $workspace->members()->detach($members);

            $this->syncWorkspaceMemberRolesAfterRemoval($workspace, $members);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    public function syncWorkspaceMemberRolesAfterRemoval(Workspace $workspace, array $members, RoleService $roleService = new RoleService()): ?int
    {
        if (empty($members) || !array_is_list($members)) {
            return null;
        }

        return $roleService->resetUserRolesInContext($members, [
            ResourceEnum::WORKSPACE, $workspace->id,
        ]);
    }

    public function syncWorkspaceMemberRolesAfterAddition(Workspace $workspace, array $members, RoleService $roleService = new RoleService()): bool
    {
        if (empty($members) || !array_is_list($members)) {
            return null;
        }

        $context = [ResourceEnum::WORKSPACE, $workspace->id];
        $data = $roleService->prepareResourceInsertData($context, RoleEnum::VIEWER->value, $members);

        return RoleUser::query()->insert($data);
    }

    /**
     * Get user joined workspaces.
     */
    public function getUserJoinedWorkspaces(User $auth, User $target, int|string $page = 1, int|string $limit = 10)
    {
        //return all target user workspaces.
        if ($auth->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ])) {
            return $target->workspaces()->paginate(perPage: $limit, page: $page);
        }

        //return only shared workspaces between auth and target user.
        return Workspace::query()
            ->select('workspaces.*')
            ->join('user_workspace', 'workspaces.id', '=', 'user_workspace.workspace_id')
            ->where(function (Builder $query) use ($target, $auth) {
                $query->where('user_workspace.user_id', $target->id)
                    ->orWhere('user_workspace.user_id', $auth->id);
            })
            ->groupBy('workspaces.id')
            ->havingRaw('COUNT(DISTINCT user_workspace.user_id) = 2')
            ->paginate(perPage: $limit, page: $page);
    }

    /**
     * Get list of workspace depending on user role.
     */
    public function getWorkspaceListByRole(User $user, array $includes, int $page, int $limit)
    {
        $query = Workspace::query()
            ->with($includes)
            ->select('workspaces.*')
            ->join('role_user', 'workspaces.id', 'role_user.workspace_id')
            ->groupBy('workspaces.id');

        if ($user->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ])) {
            return $query
                ->paginate(perPage: $limit, page: $page);
        }

        return $query
            ->where('role_user.user_id', $user->id)
            ->whereNotNull('role_user.workspace_id')
            ->paginate(perPage: $limit, page: $page);
    }
}
