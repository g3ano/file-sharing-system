<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
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
use App\Http\Resources\v1\AbilityCollection;

class WorkspaceController extends Controller
{
    protected $relationships;
    protected $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->relationships = ["owner", "members"];
    }

    /**
     * Creates new workspace.
     */
    public function createWorkspace(
        CreateWorkspaceRequest $request
    ): JsonResponse {
        $auth = User::user();

        if (
            !$auth ||
            !$auth->can(AbilityEnum::CREATE->value, Workspace::class)
        ) {
            $this->failedAsNotFound("workspace");
        }

        $data = $request->validated();
        $workspace = $this->workspaceService->createWorkspace($auth, $data);

        return $this->succeed(
            [
                "workspace" => $workspace,
            ],
            201
        );
    }

    /**
     * Get workspace data by ID.
     */
    public function getWorkspaceByID(
        Request $request,
        string $workspaceID
    ): WorkspaceResource {
        $includes = $this->getIncludedRelationships($request);
        $auth = User::user();
        $workspace = Workspace::query()
            ->with($includes)
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
    public function getWorkspaceList(Request $request): WorkspaceCollection
    {
        $page = $request->get("page") ?? $this->page;
        $limit = $request->get("limit") ?? $this->limit;
        $searchValue = $request->query("searchValue") ?? "";
        $includes = $this->getIncludedRelationships($request);

        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::LIST->value, Workspace::class)) {
            $this->failedAsNotFound("workspace");
        }

        /**
         * @var LengthAwarePaginator
         */
        $workspaces = $this->workspaceService->getWorkspaceList(
            $page,
            $limit,
            $searchValue,
            $includes
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
     * Get paginated list of user workspaces.
     */
    public function getUserWorkspaceList(
        Request $request,
        string $userID
    ): WorkspaceCollection {
        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderBy, $orderByDirection] = $this->getOrderByMeta($request);
        $includes = $this->getIncludedRelationships($request);
        $searchValue = $request->query("searchValue") ?? "";

        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (
            !$user ||
            !$auth->can(AbilityEnum::USER_WORKSPACE_LIST->value, $user)
        ) {
            $this->failedAsNotFound("user");
        }

        /**
         * @var LengthAwarePaginator
         */
        $workspaces = $this->workspaceService->getUserWorkspaceList(
            $user,
            $page,
            $limit,
            $searchValue,
            $includes,
            $orderBy,
            $orderByDirection
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
     * Adds members to workspace.
     */
    public function addWorkspaceMembers(
        AddWorkspaceMembersRequest $request,
        string $workspaceID
    ): JsonResponse {
        $workspace = Workspace::query()->where("id", $workspaceID)->first();
        $auth = User::user();

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
    ): JsonResponse {
        $workspace = Workspace::query()->where("id", $workspaceID)->first();
        $auth = User::user();

        if (
            !$workspace ||
            !$auth ||
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
    public function getWorkspaceMembers(
        Request $request,
        string $workspaceID
    ): UserCollection {
        $page = $request->get("page") ?? $this->page;
        $limit = $request->get("limit") ?? $this->limit;

        $workspace = Workspace::query()->where("id", $workspaceID)->first();
        $auth = User::user();

        if (
            !$workspace ||
            !$auth->can(AbilityEnum::WORKSPACE_MEMBER_LIST->value, $workspace)
        ) {
            $this->failedAsNotFound("workspace");
        }

        /**
         * @var LengthAwarePaginator
         */
        $members = $workspace
            ->members()
            ->orderByPivot("created_at")
            ->paginate(perPage: $limit, page: $page);

        $members = $members->through(function ($member) use (
            $auth,
            $workspace
        ) {
            $this->workspaceService->getUserCapabilitiesForWorkspaceMember(
                $auth,
                $member
            );
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

        if (!$auth->can(AbilityEnum::USER_ABILITY_VIEW->value, $user)) {
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

        $abilities = $abilities->through(function ($ability) {
            $this->workspaceService->getUserAbilityContext($ability);
            return $ability;
        });

        return new AbilityCollection($abilities);
    }

    /**
     * Update workspace member abilities.
     */
    public function updateWorkspaceMemberAbilities(
        UpdateWorkspaceMemberAbilitiesRequest $request,
        string $workspaceID,
        string $userID
    ): JsonResponse {
        $member = User::query()->where("id", $userID)->first();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (
            !$workspace ||
            !$this->workspaceService->isUserWorkspaceMember($workspace, $member)
        ) {
            $this->failedWithMessage(__("workspace.members.not_found"), 404);
        }

        $auth = User::user();

        if (
            !$auth->can(
                AbilityEnum::WORKSPACE_MEMBER_ABILITY_MANAGE->value,
                $workspace
            ) ||
            ($member->can("*", "*") && !$auth->can("*", "*"))
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
}
