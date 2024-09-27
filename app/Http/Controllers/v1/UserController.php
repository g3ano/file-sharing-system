<?php

namespace App\Http\Controllers\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use App\Http\Resources\v1\UserCollection;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $relationships;
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->relationships = [];
    }

    /**
     * Get current **Authenticated** user
     */
    public function getAuthUser()
    {
        $auth = User::user();
        $auth->includeEmail = true;

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
    public function getUserBySlug(Request $request, string $userSlug)
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

        $limit = $request->query('limit') ?? $this->limit;
        $page = $request->query('page') ?? $this->page;

        $users = $this->userService->getUserListByRole($auth, page: $page, limit: $limit);

        return new UserCollection($users);
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

        $users = User::query()
            ->onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $limit, page: $page);

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
        $limit = $request->query('limit') ?? $this->limit;
        $page = $request->query('page') ?? $this->page;

        if (!$auth) {
            $this->failedAsNotFound('user');
        }

        $users = $this->userService->searchForUsers($auth, $searchValue, $page, $limit);

        return new UserCollection($users);
    }
}
