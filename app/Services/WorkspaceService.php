<?php

namespace App\Services;

use Throwable;
use App\Models\User;
use RuntimeException;
use App\Models\Workspace;
use App\Enums\ResourceEnum;
use App\Enums\RoleEnum;
use App\Models\Role;
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
    public function addWorkspaceMembers(Workspace $workspace, array $data, RoleService $roleService)
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

            $this->syncWorkspacesMemberRolesAfterRemoval($roleService, $workspace, $members);
            $this->syncWorkspacesMemberRolesAfterAddition($roleService, $workspace, $members);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws RuntimeException
     * @throws Throwable
     */
    public function AddUserToWorkspaces(User $user, array $data, RoleService $roleService)
    {
        [
            'workspaces' => $workspaces,
        ] = $data;

        if (!array_is_list($workspaces)) {
            $this->failedAtRuntime(__('workspace.members.workspaces'), 422);
        }

        try {
            DB::beginTransaction();

            $user->workspaces()->attach($workspaces);

            $this->syncWorkspacesMemberRolesAfterRemoval(
                $roleService,
                $workspaces,
                [$user->id],
            );
            $this->syncWorkspacesMemberRolesAfterAddition(
                $roleService,
                $workspaces,
                [$user->id],
            );

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws RuntimeException
     */
    public function removeWorkspaceMembers(Workspace $workspace, array $data, RoleService $roleService)
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

            $this->syncWorkspacesMemberRolesAfterRemoval($roleService, $workspace, $members);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    public function syncWorkspacesMemberRolesAfterRemoval(RoleService $roleService, Workspace|array $workspace, array $members): ?int
    {
        if (empty($members) || !array_is_list($members)) {
            return null;
        }

        $context = [
            ResourceEnum::WORKSPACE,
            $workspace instanceof Workspace ? $workspace->id : $workspace,
        ];

        return $roleService->resetUserRolesInContext($roleService, $members, $context);
    }

    public function syncWorkspacesMemberRolesAfterAddition(RoleService $roleService, Workspace|array $workspace, array $members): bool
    {
        if (empty($members) || !array_is_list($members)) {
            return null;
        }

        $workspace = is_array($workspace) ? $workspace : [$workspace->id];
        $data = [];

        foreach ($workspace as $workspaceID) {
            $context = [ResourceEnum::WORKSPACE, $workspaceID];
            $data[] = $roleService->prepareResourceInsertData($context, RoleEnum::VIEWER->value, $members);
        }

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
    public function getWorkspaceListByRole(User $user, string|int $page, string|int $limit, ?string $searchValue = null, array $includes = [])
    {
        $query = Workspace::query()
            ->with($includes)
            ->select('workspaces.*');

        if ($searchValue) {
            $searchValue = "%$searchValue%";
            $query = $query->whereAny([
                'name', 'description',
            ], 'ILIKE', $searchValue);
        }

        if ($user->canDo([
            [RoleEnum::ADMIN],
            [RoleEnum::MANAGER],
            [RoleEnum::VIEWER],
        ])) {
            return $query->paginate(perPage: $limit, page: $page);
        }

        return $query
            ->join('role_user', 'workspaces.id', 'role_user.workspace_id')
            ->groupBy('workspaces.id')
            ->where('role_user.user_id', $user->id)
            ->whereNotNull('role_user.workspace_id')
            ->paginate(perPage: $limit, page: $page);
    }
}
