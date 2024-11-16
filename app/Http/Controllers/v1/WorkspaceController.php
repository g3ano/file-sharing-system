<?php

namespace App\Http\Controllers\v1;

use Throwable;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\AbilityEnum;
use Illuminate\Http\Request;
use App\Services\WorkspaceService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserCollection;
use App\Events\WorkspaceMembershipUpdated;
use App\Http\Resources\v1\WorkspaceResource;
use App\Http\Resources\v1\WorkspaceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Enums\WorkspaceMembershipUpdatedActionEnum;
use App\Http\Requests\v1\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\v1\Workspace\AddWorkspaceMembersRequest;
use App\Http\Requests\v1\Workspace\RemoveWorkspaceMembersRequest;
use App\Http\Requests\v1\Workspace\UpdateWorkspaceMemberAbilitiesRequest;
use App\Http\Requests\v1\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\v1\AbilityCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
    protected $relationships;
    protected $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->relationships = ["owner", "members"];
        $this->orderable = [
            "id",
            "createdAt",
            "name",
            "description",
            "deletedAt",
        ];
        $this->orderableMap = [
            "createdAt" => "created_at",
            "deletedAt" => "deleted_at",
        ];
    }

    /**
     * Creates new workspace.
     */
    public function createWorkspace(CreateWorkspaceRequest $request)
    {
        $auth = User::user();

        if (
            !$auth ||
            !$auth->can(AbilityEnum::CREATE->value, Workspace::class)
        ) {
            $this->failedAsNotFound("workspace");
        }

        $data = $request->validated();
        try {
            $workspace = $this->workspaceService->createWorkspace($auth, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        event(new WorkspaceMembershipUpdated([$auth->id], $workspace->id));

        $this->workspaceService->getUserCapabilitiesForWorkspace(
            $auth,
            $workspace
        );

        return new WorkspaceResource($workspace);
    }

    /**
     * Get workspace data by ID.
     */
    public function getWorkspaceByID(Request $request, string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (!$workspace || !$auth->can(AbilityEnum::VIEW->value, $workspace)) {
            $this->failedAsNotFound("workspace");
        }

        $this->workspaceService->getUserCapabilitiesForWorkspace(
            $auth,
            $workspace
        );

        return new WorkspaceResource($workspace);
    }

    /**
     * Get deleted workspace data by ID.
     */
    public function getDeletedWorkspaceByID(string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::onlyTrashed()
            ->where("id", $workspaceID)
            ->first();

        if (!$workspace || !$auth->can(AbilityEnum::VIEW->value, $workspace)) {
            $this->failedAsNotFound("workspace");
        }

        $this->workspaceService->getUserCapabilitiesForWorkspace(
            $auth,
            $workspace
        );

        return new WorkspaceResource($workspace);
    }

    /**
     * Get paginated list of workspaces.
     */
    public function getWorkspaceList(Request $request)
    {
        $isSearchQuery = (bool) ($request->query("searchValue") ?? "");

        if ($isSearchQuery) {
            return $this->searchWorkspaceList($request);
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $workspaces = $this->workspaceService->getWorkspaceList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $workspaces = $workspaces->through(function (Workspace $workspace) use (
            $auth
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspace(
                $auth,
                $workspace
            );
            return $workspace;
        });

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Search workspace list.
     */
    public function searchWorkspaceList(Request $request)
    {
        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $workspaces = $this->workspaceService->searchWorkspaceList(
            $searchValue,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $workspaces = $workspaces->through(function (Workspace $workspace) use (
            $auth
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspace(
                $auth,
                $workspace
            );
            return $workspace;
        });

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Get workspace list count.
     */
    public function getWorkspaceListCount()
    {
        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        return $this->succeed(
            ["data" => Workspace::query()->count()],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Get paginated list of deleted workspaces.
     */
    public function getDeletedWorkspaceList(Request $request)
    {
        $isSearchQuery = (bool) ($request->query("searchValue") ?? "");

        if ($isSearchQuery) {
            return $this->searchDeletedWorkspaceList($request);
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $workspaces = $this->workspaceService->getDeletedWorkspaceList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $workspaces = $workspaces->through(function (Workspace $workspace) use (
            $auth
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspace(
                $auth,
                $workspace
            );
            return $workspace;
        });

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Get deleted workspace list count.
     */
    public function getDeletedWorkspaceListCount()
    {
        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        return $this->succeed(
            ["data" => Workspace::onlyTrashed()->count()],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Search deleted workspace list.
     */
    public function searchDeletedWorkspaceList(Request $request)
    {
        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $workspaces = $this->workspaceService->searchDeletedWorkspaceList(
            $searchValue,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $workspaces = $workspaces->through(function (Workspace $workspace) use (
            $auth
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspace(
                $auth,
                $workspace
            );
            return $workspace;
        });

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Adds members to workspace.
     */
    public function addWorkspaceMembers(
        AddWorkspaceMembersRequest $request,
        string $workspaceID
    ) {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::WORKSPACE_MEMBER_ADD->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        [
            "members" => $members,
        ] = $request->validated();

        try {
            $this->workspaceService->addWorkspaceMembers($workspace, $members);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        event(new WorkspaceMembershipUpdated($members, $workspace->id));

        return $this->succeedWithStatus();
    }

    /**
     * Removes members from workspace.
     */
    public function removeWorkspaceMembers(
        RemoveWorkspaceMembersRequest $request,
        string $workspaceID
    ) {
        $workspace = Workspace::query()->where("id", $workspaceID)->first();
        $auth = User::user();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::WORKSPACE_MEMBER_REMOVE->value, [
                Workspace::class,
                $workspace,
            ])
        ) {
            $this->failedAsNotFound("workspace");
        }

        [
            "members" => $members,
        ] = $request->validated();

        try {
            $this->workspaceService->removeWorkspaceMembers(
                $workspace,
                $members
            );
        } catch (Throwable $e) {
            Log::error(
                "Error removing workspace members: " . $e->getMessage(),
                [
                    "workspace_id" => $workspace->id,
                    "members" => $members,
                ]
            );
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        event(
            new WorkspaceMembershipUpdated(
                $members,
                $workspace->id,
                WorkspaceMembershipUpdatedActionEnum::REMOVE
            )
        );

        return $this->succeedWithStatus();
    }

    /**
     * Get paginated list of workspace Members.
     */
    public function getWorkspaceMembers(Request $request, string $workspaceID)
    {
        $workspace = Workspace::query()->where("id", $workspaceID)->first();
        $auth = User::user();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::WORKSPACE_MEMBER_LIST->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $searchValue = $request->query("searchValue") ?? "";

        /**
         * @var LengthAwarePaginator
         */
        $members = $workspace
            ->members()
            ->orderByPivot($orderByField, $orderByDirection);

        if ($searchValue) {
            $members = $members->whereAny(
                [
                    "first_name",
                    "last_name",
                    "email",
                    "username",
                    DB::raw("CONCAT(first_name, ' ', last_name)"),
                ],
                "ILIKE",
                "%{$searchValue}%"
            );
        }

        $members = $members->paginate(perPage: $limit, page: $page);
        $members = $members->through(function ($member) use (
            $auth,
            $workspace
        ) {
            $this->workspaceService->getWorkspaceMemberState(
                $workspace,
                $member
            );

            return $member;
        });

        return new UserCollection($members);
    }

    /**
     * Get workspace member list of abilities.
     */
    public function getWorkspaceMemberAbilities(
        Request $request,
        string $workspaceID,
        string $userID
    ) {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$this->workspaceService->isUserWorkspaceMember($workspace, $user)
        ) {
            $this->failedWithMessage(__("workspace.members.not_found"), 404);
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);

        $abilities = $this->workspaceService->getWorkspaceMemberAbilities(
            $workspace,
            $user,
            $page,
            $limit
        );

        return new AbilityCollection($abilities);
    }

    /**
     * Update workspace member abilities.
     */
    public function updateWorkspaceMemberAbilities(
        UpdateWorkspaceMemberAbilitiesRequest $request,
        string $workspaceID,
        string $userID
    ) {
        $member = User::query()->where("id", $userID)->first();
        $workspace = once(
            fn() => Workspace::query()->where("id", $workspaceID)->first()
        );

        if (
            !$workspace ||
            !$this->workspaceService->isUserWorkspaceMember($workspace, $member)
        ) {
            $this->failedWithMessage(__("workspace.members.not_found"), 404);
        }

        $auth = User::user();

        if (
            !$auth->can(AbilityEnum::USER_ABILITY_MANAGE->value, $member) &&
            !$auth->can(AbilityEnum::USER_ABILITY_MANAGE->value, $workspace)
        ) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();

        $this->workspaceService->updateWorkspaceMemberAbilities(
            $member,
            $workspace,
            $data
        );

        return $this->succeedWithStatus();
    }

    /**
     * Get paginated list of user workspaces.
     */
    public function getUserWorkspaceList(Request $request, string $userID)
    {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (
            !$user ||
            !$auth->can(AbilityEnum::USER_WORKSPACE_LIST->value, $user)
        ) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $searchValue = $request->query("searchValue") ?? "";

        $workspaces = $this->workspaceService->getUserWorkspaceList(
            $user,
            $page,
            $limit,
            $orderByField,
            $orderByDirection,
            $searchValue
        );

        $workspaces = $workspaces->through(function (Workspace $workspace) use (
            $auth
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspace(
                $auth,
                $workspace
            );
            $this->workspaceService->getWorkspaceMemberState($workspace, $auth);
            return $workspace;
        });

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Soft deletes a workspace.
     */
    public function deleteWorkspace(string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::DELETE->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        if (!$workspace->delete()) {
            Log::error("Failed to deleted workspace", [
                "workspace" => $workspace,
            ]);

            $this->failedWithMessage(__("workspace.deleted.soft"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Restore soft deleted workspace.
     */
    public function restoreWorkspace(string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::onlyTrashed()
            ->where("id", $workspaceID)
            ->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::RESTORE->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        if (!$workspace->restore()) {
            Log::error("Failed to restore workspace", [
                "workspace" => $workspace,
            ]);

            $this->failedWithMessage(__("workspace.deleted.restore"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Force deletes a workspace.
     */
    public function forceDeleteWorkspace(string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::withTrashed()
            ->where("id", $workspaceID)
            ->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::FORCE_DELETE->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        if (!$workspace->forceDelete()) {
            Log::error("Failed to force delete workspace", [
                "workspace" => $workspace,
            ]);

            $this->failedWithMessage(__("workspace.deleted.force_delete"), 500);
        }

        return $this->succeedWithStatus();
    }

    /**
     * Update workspace.
     */
    public function updateWorkspace(
        UpdateWorkspaceRequest $request,
        string $workspaceID
    ) {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::UPDATE->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        $data = $request->validated();

        try {
            $status = $workspace->update([
                ...$data,
                "name" => ucfirst($data["name"]),
                "description" => ucfirst($data["description"]),
            ]);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        if (!$status) {
            $this->failedWithMessage(__("workspace.update"), 500);

            Log::error(__("workspace.update"), [
                "workspace" => $workspace,
            ]);
        }

        return $this->succeedWithStatus();
    }
}
