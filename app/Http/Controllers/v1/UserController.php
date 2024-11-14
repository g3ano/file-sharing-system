<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Throwable;
use App\Models\User;
use App\Enums\AbilityEnum;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use App\Http\Resources\v1\UserCollection;
use App\Http\Resources\v1\AbilityCollection;
use App\Http\Requests\v1\User\UpdateUserAbilitiesRequest;
use App\Http\Requests\v1\User\UpdateUserGlobalAbilitiesRequest;
use App\Http\Requests\v1\User\UpdateUserRequest;
use Illuminate\Http\Response;

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
        $this->orderable = ["id", "createdAt", "firstName", "deletedAt"];
        $this->orderableMap = [
            "createdAt" => "created_at",
            "deletedAt" => "deleted_at",
            "firstName" => "first_name",
        ];
    }

    /**
     * Determine whether if any user is authenticated.
     */
    public function getIsUserAuth()
    {
        return $this->succeed([
            "authenticated" => Auth::guard("web")->check(),
        ]);
    }

    /**
     * Get current **Authenticated** user
     */
    public function getAuthUser()
    {
        $auth = User::user();

        if (!$auth) {
            $this->failedAsNotFound("user");
        }

        $auth->includeEmail = true;

        $this->userService->getUserCapabilitiesForUser($auth, $auth, true);

        return new UserResource($auth);
    }

    /**
     * Get user by ID.
     */
    public function getUserByID(Request $request, string $userID)
    {
        $user = User::query()->where("id", $userID)->first();
        $auth = User::user();

        if (!$user || !$auth->can(AbilityEnum::VIEW->value, $user)) {
            $this->failedAsNotFound("user");
        }

        $user->includeEmail = $request->boolean("includeEmail") ?? false;
        $this->userService->getUserCapabilitiesForUser($auth, $user);

        return new UserResource($user);
    }

    /**
     * Get user by ID.
     */
    public function getDeletedUserByID(Request $request, string $userID)
    {
        $user = User::onlyTrashed()->where("id", $userID)->first();
        $auth = User::user();

        if (!$user || !$auth->can(AbilityEnum::VIEW->value, $user)) {
            $this->failedAsNotFound("user");
        }

        $user->includeEmail = $request->boolean("includeEmail") ?? false;
        $this->userService->getUserCapabilitiesForUser($auth, $user);

        return new UserResource($user);
    }

    /**
     * Get paginated list of users.
     */
    public function getUserList(Request $request)
    {
        $isSearchQuery = (bool) ($request->query("searchValue") ?? "");

        if ($isSearchQuery) {
            return $this->searchUserList($request);
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $includeEmail = $request->boolean("includeEmail") ?? false;

        $users = $this->userService->getUserList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $users = $users->through(function ($item) use ($auth, $includeEmail) {
            $item->includeEmail = $includeEmail;
            $this->userService->getUserCapabilitiesForUser($auth, $item);
            return $item;
        });

        return new UserCollection($users);
    }

    /**
     * Search user list.
     */
    public function searchUserList(Request $request)
    {
        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedWithMessage(__("user.not_found"), 404);
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $includeEmail = $request->boolean("includeEmail") ?? false;

        $users = $this->userService->searchUserList(
            $searchValue,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $users->through(function ($item) use ($auth, $includeEmail) {
            $item->includeEmail = $includeEmail;
            $this->userService->getUserCapabilitiesForUser($auth, $item);
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

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        $count = User::query()->count();

        return $this->succeed(
            [
                "data" => $count,
            ],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Get paginated list of deleted users.
     */
    public function getDeletedUserList(Request $request)
    {
        $isSearchQuery = (bool) ($request->query("searchValue") ?? "");

        if ($isSearchQuery) {
            return $this->searchDeletedUserList($request);
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $includeEmail = $request->boolean("includeEmail") ?? false;

        $users = $this->userService->getUserDeletedList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $users = $users->through(function ($item) use ($auth, $includeEmail) {
            $item->includeEmail = $includeEmail;
            $this->userService->getUserCapabilitiesForUser($auth, $item);
            return $item;
        });

        return new UserCollection($users);
    }

    /**
     * Search deleted user list.
     */
    public function searchDeletedUserList(Request $request)
    {
        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $includeEmail = $request->boolean("includeEmail") ?? false;

        $users = $this->userService->searchDeletedUserList(
            $searchValue,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $users->through(function ($item) use ($auth, $includeEmail) {
            $item->includeEmail = $includeEmail;
            $this->userService->getUserCapabilitiesForUser($auth, $item);
            return $item;
        });

        return new UserCollection($users);
    }

    /**
     * Get users count.
     */
    public function getDeletedUserListCount()
    {
        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        $count = User::query()->onlyTrashed()->count();

        return $this->succeed(
            [
                "data" => $count,
            ],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Update user.
     */
    public function updateUser(UpdateUserRequest $request, string $userID)
    {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (!$auth->can(AbilityEnum::UPDATE->value, $user)) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();

        try {
            $status = $this->userService->updateUser($user, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        if (!$status) {
            $this->failedWithMessage(__("user.update"), 500);

            Log::error(__("user.update"), [
                "user" => $user,
            ]);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Soft-deletes user.
     */
    public function deleteUser(string $userID): JsonResponse
    {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (!$user || !$auth->can(AbilityEnum::DELETE->value, $user)) {
            $this->failedAsNotFound("user");
        }

        if (!$user->delete()) {
            Log::error("Failed to deleted user", [
                "user" => $user,
            ]);

            $this->failedWithMessage(__("user.deleted.soft"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Restore soft-deleted user.
     */
    public function restoreUser(string $userID)
    {
        $auth = User::user();
        /**
         * @var User
         */
        $user = User::onlyTrashed()->where("id", $userID)->first();

        if (!$user || !$auth->can(AbilityEnum::RESTORE->value, $user)) {
            $this->failedAsNotFound("user");
        }

        if (!$user->restore()) {
            Log::error("Failed to deleted user", [
                "user" => $user,
            ]);

            $this->failedWithMessage(__("user.deleted.restore"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Force delete user.
     */
    public function forceDeleteUser(string $userID)
    {
        $auth = User::user();
        /**
         * @var User
         */
        $user = User::withTrashed()->where("id", $userID)->first();

        if (!$user || !$auth->can(AbilityEnum::FORCE_DELETE->value, $user)) {
            $this->failedAsNotFound("user");
        }

        if (!$user->forceDelete()) {
            Log::error("Failed to deleted user", [
                "user" => $user,
            ]);

            $this->failedWithMessage(__("user.delete.force_delete"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Get paginated list of user abilities.
     */
    public function getUserAbilities(Request $request, string $userID)
    {
        $isSearchQuery = (bool) ($request->query("searchValue") ?? "");

        if ($isSearchQuery) {
            return $this->searchUserAbilities($request, $userID);
        }

        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        $auth = User::user();
        [$page, $limit] = $this->getPaginatorMetadata($request);

        $abilities = $this->userService->getUserAbilities($user, $page, $limit);

        return new AbilityCollection($abilities);
    }

    /**
     * Search user abilities.
     */
    public function searchUserAbilities(Request $request, string $userID)
    {
        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);

        $abilities = $this->userService->searchUserAbilities(
            $user,
            $searchValue,
            $page,
            $limit
        );

        return new AbilityCollection($abilities);
    }

    /*
     * Get user global abilities (abilities applies class level).
     */
    public function getUserGlobalAbilities(Request $request, string $userID)
    {
        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        $scope = $request->query("scope");

        $abilities = $this->userService->getUserGlobalAbilities(
            $user,
            $page,
            $limit,
            $scope
        );

        return new AbilityCollection($abilities);
    }

    /**
     * Gets user abilities for another target user.
     */
    public function getUserAbilitiesForUser(
        Request $request,
        string $userID,
        string $targetID
    ) {
        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        $target = User::query()->where("id", $targetID)->first();
        [$page, $limit] = $this->getPaginatorMetadata($request);

        $abilities = $this->userService->getUserAbilitiesForUser(
            $user,
            $target,
            $page,
            $limit
        );

        return new AbilityCollection($abilities);
    }

    /**
     * Update user global abilities (applied at class level).
     */
    public function updateUserGlobalAbilities(
        UpdateUserGlobalAbilitiesRequest $request,
        string $userID
    ) {
        $user = User::query()->where("id", $userID)->first();
        $auth = User::user();

        if (
            !$user ||
            !$auth->can(AbilityEnum::USER_ABILITY_MANAGE->value, $user)
        ) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();

        $this->userService->updateUserGlobalAbilities($user, $data);

        return $this->succeedWithStatus();
    }

    /**
     * Update user abilities against another user.
     */
    public function updateUserAbilitiesForUser(
        UpdateUserAbilitiesRequest $request,
        string $userID,
        string $targetID
    ) {
        $user = User::query()->where("id", $userID)->first();
        $targetUser = User::query()->where("id", $targetID)->first();
        $auth = User::user();

        if (
            !$user ||
            !$targetUser ||
            !$auth->can(AbilityEnum::USER_ABILITY_MANAGE->value, $user)
        ) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();

        $this->userService->updateUserAbilitiesForUser(
            $user,
            $targetUser,
            $data
        );

        return $this->succeedWithStatus();
    }
}
