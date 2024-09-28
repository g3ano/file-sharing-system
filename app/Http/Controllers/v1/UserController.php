<?php

namespace App\Http\Controllers\v1;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use App\Http\Resources\v1\UserCollection;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userService;
    protected $relationships;
    protected array $orderable;
    protected array $orderableMap;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->relationships = [];
        $this->orderable = [
            'id', 'createdAt', 'firstName',
        ];
        $this->orderableMap = [
            'createdAt' => 'created_at',
            'firstName' => 'first_name',
        ];
    }

    /**
     * Get current **Authenticated** user
     */
    public function getAuthUser()
    {
        $auth = User::user();
        $auth->abilities = [
            'canViewAllUsers' => $auth->canDo([
                [RoleEnum::ADMIN],
                [RoleEnum::MANAGER],
                [RoleEnum::VIEWER],
            ]),
            'canViewWorkspaceMembers' => $auth->isAnyWorkspaceManager(),
            'canViewProjectMembers' => $auth->isAnyProjectManager(),
            'canCreateUser' => $auth->can('createUser', User::class),
        ];

        return new UserResource($auth);
    }

    /**
     * Get user by ID.
     */
    public function getUserByID(string $userID)
    {
        $user = User::query()
            ->where('id', $userID)
            ->first();
        $auth = User::user();

        if (!$user || !$auth->can('viewUser', [
            User::class, $user,
        ])) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        return new UserResource($user);
    }

    /**
     * Get user by slug.
     */
    public function getUserBySlug(string $userSlug)
    {
        if ($userSlug === '@me') {
            return $this->getAuthUser();
        }

        $user = User::query()
            ->where('slug', $userSlug)
            ->first();
        $auth = User::user();

        if (!$user || !$auth->can('viewUser', [
            User::class, $user,
        ])) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        return new UserResource($user);
    }

    /**
     * Determine whether current user is authenticated.
     */
    public function getIsUserAuth()
    {
        return $this->succeed([
            'authenticated' => Auth::guard('web')->check(),
        ]);
    }

    /**
     * Get paginated list of users.
     */
    public function getUserList(Request $request)
    {
        $auth = User::user();

        if (!$auth || !$auth->can('viewAnyUser', User::class)) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        $isSearchQuery = (bool) ($request->query('searchValue') ?? '');

        if ($isSearchQuery) {
            return $this->searchUserList($request);
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderBy, $orderByDir] = $this->getOrderByMeta($request);

        /**
         * @var LengthAwarePaginator
         */
        $users = $this->userService->getUserListQueryBuilder($auth)
            ->orderBy($orderBy, $orderByDir)
            ->paginate(perPage: $limit, page: $page);

        $users->through(function ($item) use ($auth) {
            $item->includeEmail = true;
            $this->userService->getAuthUserAbilitiesTo(auth: $auth, target: $item);
            return $item;
        });

        return new UserCollection($users);
    }

    /**
     * Get users count.
     */
    public function getUserListCount()
    {
        $auth = User::user();

        if (!$auth || !$auth->can('viewAnyUser', User::class)) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        $count = $this->userService->getUserListQueryBuilder($auth)
            ->count();

        return $this->succeed([
            'data' => $count,
        ], 200, false);
    }

    /**
     * Get paginated list of users.
     */
    public function getDeletedUserList(Request $request)
    {
        $auth = User::user();

        if (!$auth || !$auth->can('viewAnyUser', User::class)) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        $limit = $request->query('limit') ?? $this->limit;
        $page = $request->query('page') ?? $this->page;

        /**
         * @var LengthAwarePaginator
         */
        $users = User::query()
            ->onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $limit, page: $page);

        $users->through(function ($item) use ($auth) {
            $item->includeEmail = true;
            $this->userService->getAuthUserAbilitiesTo(auth: $auth, target: $item);
            return $item;
        });

        return new UserCollection($users);
    }

    /**
     * Deletes user from system.
     */
    public function deleteUser(string $userID)
    {
        $auth = User::user();
        $user = User::query()->where('id', $userID)->first();

        if (!$user || !$auth->can('deleteUser', [
            User::class,
            $user,
        ])) {
            $this->failedAsNotFound('user');
        }

        if (!$user->delete()) {
            Log::error('Failed to deleted user', [
                'userID' => $user->id,
            ]);

            $this->failedWithMessage(__('user.delete.soft'), 500);
        }

        return $this->succeedWithStatus();
    }

    public function searchUserList(Request $request)
    {
        $searchValue = $request->query('searchValue') ?? '';

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $auth = User::user();

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderBy, $orderByDir] = $this->getOrderByMeta($request);

        if (!$auth) {
            $this->failedAsNotFound('user');
        }

        /**
         * @var LengthAwarePaginator
         */
        $users = $this->userService->searchForUsers($auth, $searchValue, $page, $limit, $orderBy, $orderByDir);

        $users->through(function ($item) use ($auth) {
            $item->includeEmail = true;
            $this->userService->getAuthUserAbilitiesTo(auth: $auth, target: $item);

            return $item;
        });

        return new UserCollection($users);
    }
}
