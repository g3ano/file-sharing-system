<?php

namespace App\Http\Controllers\v1;

use Throwable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Role\UserIsRoleRequest;
use App\Http\Resources\v1\RoleCollection;
use App\Http\Requests\v1\Role\GrantUserRoleRequest;
use App\Http\Requests\v1\Role\GrantUserProjectRoleRequest;
use App\Http\Requests\v1\Role\GrantUserWorkspaceRoleRequest;
use App\Models\Project;
use App\Models\Workspace;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
        $this->relationships = [
            'workspaces', 'projects',
        ];
    }

    public function getAuthUserRoles(Request $request)
    {
        $page = $request->query('page') ?? $this->page;
        $limit = $request->query('limit') ?? $this->limit;
        $auth = User::user();

        if (!$auth?->can('viewAuthUserRoles', Role::class)) {
            $this->failedAsNotFound('user');
        }

        $roles = $this->roleService->getUserRoles($auth, $page, $limit);

        return new RoleCollection($roles);
    }

    public function getUserRoles(Request $request, string $userID)
    {
        $auth = User::user();
        $user = User::query()->where('id', $userID)->first();

        if (!$user || !$auth->can('viewUserRoles', Role::class)) {
            $this->failedAsNotFound('user');
        }

        $page = $request->query('page') ?? $this->page;
        $limit = $request->query('limit') ?? $this->limit;

        $roles = $this->roleService->getUserRoles($user, $page, $limit);

        return new RoleCollection($roles);
    }

    /**
     * Checks whether authenticated user have a role.
     */
    public function getAuthUserIsRole(UserIsRoleRequest $request)
    {
        $auth = User::user();
        $data = $request->validated();

        try {
            return $this->succeed([
                'can' => $this->roleService->checkUserRole($auth, $data),
            ]);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }
    }

    /**
     * Checks whether a user have a role.
     */
    public function getUserIsRole(UserIsRoleRequest $request, string $userID)
    {
        $user = User::query()
            ->where('id', $userID)
            ->first();

        if (!$user) {
            $this->failedAsNotFound('user');
        }

        $data = $request->validated();

        try {
            return $this->succeed([
                'can' => $this->roleService->checkUserRole($user, $data),
            ]);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }
    }

    /**
     * Grant a user a role in the global context.
     */
    public function grantUserGlobalRole(GrantUserRoleRequest $request, string $userID)
    {
        $user = User::query()->where('id', $userID)->first();
        $auth = User::user();

        $data = $request->validated();

        if (!$user || !$auth->can('grantUserGlobalRole', [
            Role::class,
            $data['role_id'],
        ])) {
            $this->failedAsNotFound('user');
        }

        try {
            $this->roleService->grantUserRole($user, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e),
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Grant a user a role in the Workspace context.
     */
    public function grantUserWorkspaceRole(GrantUserWorkspaceRoleRequest $request, string $userID)
    {
        $user = User::query()->where('id', $userID)->first();
        $auth = User::user();
        $data = $request->validated();
        $workspace = Workspace::query()->where('id', $data['context'][1])->first();

        if (!$user || !$workspace || !$auth->can('grantUserWorkspaceRole', [
            Role::class,
            $workspace, $user,
        ])) {
            $this->failedAsNotFound('user');
        }

        try {
            $this->roleService->grantUserRole($user, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e),
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Grant a user a role in the Project context.
     */
    public function grantUserProjectRole(GrantUserProjectRoleRequest $request, string $userID)
    {
        $user = User::query()->where('id', $userID)->first();
        $auth = User::user();
        $data = $request->validated();
        $project = Project::query()->where('id', $data['context'][1])->first();

        if (!$user || !$project || !$auth->can('grantUserProjectRole', [
            Role::class,
            $project, $user,
        ])) {
            $this->failedAsNotFound('user');
        }

        try {
            $this->roleService->grantUserRole($user, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e),
            );
        }

        return $this->succeedWithStatus();
    }
}
