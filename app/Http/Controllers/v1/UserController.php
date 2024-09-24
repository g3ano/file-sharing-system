<?php

namespace App\Http\Controllers\v1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use App\Http\Resources\v1\UserCollection;

class UserController extends Controller
{
    /**
     * Get current **Authenticated** user
     */
    public function getAuthUser()
    {
        return new UserResource(User::user());
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

        $limit = $request->query('limit') ?? $this->limit;
        $page = $request->query('page') ?? $this->page;

        $users = User::query()->paginate(perPage: $limit, page: $page);

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

        $this->succeedWithStatus();
    }
}
