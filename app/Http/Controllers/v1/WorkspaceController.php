<?php

declare(strict_types=1);

namespace App\Http\Controllers\v1;

use Throwable;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use App\Services\WorkspaceService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserCollection;
use App\Http\Resources\v1\WorkspaceCollection;
use App\Http\Requests\v1\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\v1\Workspace\AddWorkspaceMembersRequest;
use App\Http\Requests\v1\Workspace\RemoveWorkspaceMembersRequest;

class WorkspaceController extends Controller
{
    protected $relationships;
    protected $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->relationships = [
            'owner', 'members',
        ];
    }

    /**
     * Creates new workspace.
     */
    public function createWorkspace(CreateWorkspaceRequest $request)
    {
        $auth = User::user();

        if (!$auth || !$auth->can('createWorkspace', Workspace::class)) {
            $this->failedAsNotFound('workspace');
        }

        $data = $request->validated();
        $workspace = $this->workspaceService->createWorkspace($auth, $data);

        return $this->succeed([
            'workspace' => $workspace,
        ], 201);
    }

    /**
     * Get paginated list of workspaces.
     */
    public function getWorkspaceList(Request $request)
    {
        $page = $request->get('page') ?? $this->page;
        $limit = $request->get('limit') ?? $this->limit;
        $includes = $this->getIncludedRelationships($request);

        $auth = User::user();

        if (!$auth || !$auth->can('viewAnyWorkspace', Workspace::class)) {
            $this->failedAsNotFound('workspace');
        }

        $workspaces = Workspace::query()
            ->with($includes)
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: $limit, page: $page);

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Adds members from workspace.
     */
    public function addWorkspaceMembers(AddWorkspaceMembersRequest $request, string $workspaceID)
    {
        $workspace = Workspace::query()
            ->where('id', $workspaceID)
            ->first();
        $auth = User::user();

        if (!$workspace || !$auth || !$auth->can('addWorkspaceMembers', [
            Workspace::class, $workspace,
        ])) {
            $this->failedAsNotFound('workspace');
        }

        $members = $request->validated();

        try {
            $this->workspaceService->addWorkspaceMembers($workspace, $members);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e),
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Removes members from workspace.
     */
    public function removeWorkspaceMembers(RemoveWorkspaceMembersRequest $request, string $workspaceID)
    {
        $workspace = Workspace::query()
            ->where('id', $workspaceID)
            ->first();
        $auth = User::user();

        if (!$workspace || !$auth || !$auth->can('addWorkspaceMembers', [
            Workspace::class, $workspace,
        ])) {
            $this->failedAsNotFound('workspace');
        }

        $members = $request->validated();

        try {
            $this->workspaceService->removeWorkspaceMembers($workspace, $members);
        } catch (Throwable $e) {
            Log::error('Error removing workspace members: ' . $e->getMessage(), [
                'workspace_id' => $workspace->id,
                'members' => $members,
            ]);
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e),
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Get paginated list of workspace Members.
     */
    public function getWorkspaceMembers(Request $request, string $workspaceID)
    {
        $page = $request->get('page') ?? $this->page;
        $limit = $request->get('limit') ?? $this->limit;

        $workspace = Workspace::query()
            ->where('id', $workspaceID)
            ->first();
        $auth = User::user();

        if (!$workspace || !$auth->can('viewWorkspaceMembers', [
            Workspace::class, $workspace,
        ])) {
            $this->failedAsNotFound('workspace');
        }

        $members = $workspace
            ->members()
            ->orderByPivot('created_at')
            ->paginate(perPage: $limit, page: $page);

        return new UserCollection($members);
    }

    /**
     * Gets user joined workspaces.
     */
    public function getUserJoinedWorkspaceListByID(Request $request, string $userID)
    {
        $user = User::query()->where('id', $userID)->first();
        $auth = User::user();

        if (!$user || !$auth->can('viewUserJoinedWorkspaces', [
            Workspace::class, $user,
        ])) {
            $this->failedAsNotFound('user');
        }

        $page = $request->get('page') ?? $this->page;
        $limit = $request->get('limit') ?? $this->limit;

        $workspaces = $this->workspaceService->getUserJoinedWorkspaces($auth, $user, $page, $limit);

        return new WorkspaceCollection($workspaces);
    }

    /**
     * Gets user joined workspaces.
     */
    public function getUserJoinedWorkspaceListBySlug(Request $request, string $userSlug)
    {
        $user = User::query()->where('slug', $userSlug)->first();
        $auth = User::user();

        $page = $request->get('page') ?? $this->page;
        $limit = $request->get('limit') ?? $this->limit;

        $workspaces = $this->workspaceService->getUserJoinedWorkspaces($auth, $user, $page, $limit);

        return new WorkspaceCollection($workspaces);
    }
}
