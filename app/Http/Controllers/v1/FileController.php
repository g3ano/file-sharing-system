<?php

namespace App\Http\Controllers\v1;

use App\Enums\AbilityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\File\CreateFileRequest;
use App\Http\Requests\v1\File\RenameFileRequest;
use App\Http\Requests\v1\File\UpdateUserAbilitiesRequest;
use App\Http\Resources\v1\AbilityCollection;
use App\Http\Resources\v1\FileCollection;
use App\Http\Resources\v1\FileResource;
use App\Models\File;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\FileService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
        $this->orderable = [
            "id",
            "createdAt",
            "name",
            "type",
            "extension",
            "size",
            "deletedAt",
        ];
        $this->orderableMap = [
            "createdAt" => "created_at",
            "deletedAt" => "deleted_at",
        ];
    }

    /**
     * Stores file into disc, and persist its metadata.
     */
    public function createFile(CreateFileRequest $request)
    {
        [
            "project_id" => $fileID,
        ] = $request->validated();

        $auth = User::user();
        $project = Project::query()->where("id", $fileID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_FILES_ADD->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        try {
            $metadata = $this->fileService->createFile(
                $project,
                $auth,
                $request->file("file")
            );
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        $this->fileService->getUserCapabilitiesForFile($auth, $metadata);

        return $this->succeed(
            ["data" => new FileResource($metadata)],
            Response::HTTP_CREATED,
            false
        );
    }

    public function downloadFile(string $fileID)
    {
        $auth = User::user();
        $file = File::query()->where("id", $fileID)->first();

        if (!$file || !$auth->can(AbilityEnum::FILE_DOWNLOAD->value, $file)) {
            $this->failedAsNotFound("file");
        }

        return ResponseFacade::streamDownload(
            function () use ($file) {
                $path = Storage::path($file->path);
                readfile($path);
            },
            "{$file->name}.{$file->extension}",
            ["Content-Type" => $file->type]
        );
    }

    /**
     * Get paginated list of files.
     */
    public function getFileList(Request $request)
    {
        if ($request->get("searchValue")) {
            return $this->searchFileList($request);
        }

        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getFileList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get files count.
     */
    public function getFileListCount()
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        return $this->succeed(
            ["data" => File::query()->count()],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Search files.
     */
    public function searchFileList(Request $request)
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->searchFileList(
            $page,
            $limit,
            $searchValue,
            $orderByField,
            $orderByDirection
        );

        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get paginated list of deleted files.
     */
    public function getDeletedFileList(Request $request)
    {
        if ($request->get("searchValue")) {
            return $this->searchDeletedFileList($request);
        }

        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getDeletedFileList(
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );

        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get deleted files count.
     */
    public function getDeletedFileListCount()
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        return $this->succeed(
            ["data" => File::onlyTrashed()->count()],
            Response::HTTP_OK,
            false
        );
    }

    /**
     * Search deleted files.
     */
    public function searchDeletedFileList(Request $request)
    {
        $auth = User::user();

        if (!$auth->can(AbilityEnum::LIST->value, File::class)) {
            $this->failedAsNotFound("file");
        }

        $searchValue = $request->query("searchValue") ?? "";

        if (!$searchValue) {
            return $this->succeedWithPagination();
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->searchDeletedFileList(
            $page,
            $limit,
            $searchValue,
            $orderByField,
            $orderByDirection
        );

        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get project file list.
     */
    public function getProjectFileList(Request $request, string $projectID)
    {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_FILES_LIST->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getProjectFiles(
            $project,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get workspace file list (through its related projects).
     */
    public function getWorkspaceFileList(Request $request, string $workspaceID)
    {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (!$workspace || !$auth->can(AbilityEnum::VIEW->value, $workspace)) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getWorkspaceFiles(
            $workspace,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get user file list.
     */
    public function getUserFileList(Request $request, string $userID)
    {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (!$user || !$auth->can(AbilityEnum::USER_FILE_LIST->value, $user)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);
        $searchValue = $request->query("searchValue") ?? "";

        $files = $this->fileService->getUserFiles(
            $user,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get user trashed file list.
     */
    public function getUserTrashedFileList(Request $request, string $userID)
    {
        $auth = User::user();
        $user = User::query()->where("id", $userID)->first();

        if (!$user || !$auth->can(AbilityEnum::USER_FILE_LIST->value, $user)) {
            $this->failedAsNotFound("user");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getUserTrashedFiles(
            $user,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get project trashed file list.
     */
    public function getProjectTrashedFileList(
        Request $request,
        string $projectID
    ) {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !$auth->can(AbilityEnum::PROJECT_FILES_LIST->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getProjectTrashedFiles(
            $project,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Get workspace trashed file list (through its related projects).
     */
    public function getWorkspaceTrashedFileList(
        Request $request,
        string $workspaceID
    ) {
        $auth = User::user();
        $workspace = Workspace::query()->where("id", $workspaceID)->first();

        if (!$workspace || !$auth->can(AbilityEnum::VIEW->value, $workspace)) {
            $this->failedAsNotFound("workspace");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);
        [$orderByField, $orderByDirection] = $this->getOrderByMeta($request);

        $files = $this->fileService->getWorkspaceTrashedFiles(
            $workspace,
            $page,
            $limit,
            $orderByField,
            $orderByDirection
        );
        $files = $files->through(function ($file) use ($auth) {
            $this->fileService->getUserCapabilitiesForFile($auth, $file);
            return $file;
        });

        return new FileCollection($files);
    }

    /**
     * Deletes file from a project.
     */
    public function deleteProjectFile(string $projectID, string $fileID)
    {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !(new ProjectService())->isUserProjectMember($project, $auth) ||
            !$auth->can(AbilityEnum::PROJECT_FILES_REMOVE->value, $project)
        ) {
            $this->failedAsNotFound("project");
        }

        return $this->deleteFile($fileID);
    }

    /**
     * Adds file to project.
     */
    public function addProjectFile(
        CreateFileRequest $request,
        string $projectID
    ) {
        $auth = User::user();
        $project = Project::query()->where("id", $projectID)->first();

        if (
            !$project ||
            !(new ProjectService())->isUserProjectMember($project, $auth)
        ) {
            $this->failedAsNotFound("project");
        }

        return $this->createFile($request);
    }

    /**
     * Renames a file.
     */
    public function renameFile(RenameFileRequest $request, string $fileID)
    {
        $auth = User::user();
        $file = File::query()->where("id", $fileID)->first();

        if (!$file || !$auth->can(AbilityEnum::UPDATE->value, $file)) {
            $this->failedAsNotFound("file");
        }

        $data = $request->validated();

        $this->fileService->renameFile($file, $data);

        return $this->succeedWithStatus();
    }

    /**
     * Soft deletes a file.
     */
    public function deleteFile(string $fileID)
    {
        $auth = User::user();
        $file = File::query()->where("id", $fileID)->first();

        if (!$file || !$auth->can(AbilityEnum::DELETE->value, $file)) {
            $this->failedAsNotFound("file");
        }

        if (!$file->delete()) {
            Log::error(__("file.delete"), [
                "file" => $file,
            ]);

            $this->failedWithMessage(
                __("file.delete"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->succeedWithStatus();
    }

    public function forceDeleteFile(string $fileID)
    {
        $auth = User::user();
        $file = File::withTrashed()->where("id", $fileID)->first();

        if (!$file || !$auth->can(AbilityEnum::FORCE_DELETE->value, $file)) {
            $this->failedAsNotFound("file");
        }

        try {
            $this->fileService->forceDeleteFile($file);
        } catch (Throwable $e) {
            Log::error(__("file.force_delete"), [
                "file" => $file,
            ]);
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Restores a deleted file.
     */
    public function restoreFile(string $fileID)
    {
        $auth = User::user();
        $file = File::withTrashed()->where("id", $fileID)->first();

        if (!$file || !$auth->can(AbilityEnum::RESTORE->value, $file)) {
            $this->failedAsNotFound("file");
        }

        if (!$file->restore()) {
            Log::error(__("file.restore"), [
                "file" => $file,
            ]);

            $this->failedWithMessage(
                __("file.restore"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->succeedWithStatus();
    }

    /**
     * Get user abilities for file.
     */
    public function getUserAbilitiesForFile(
        Request $request,
        string $fileID,
        string $userID
    ) {
        $user = User::query()->where("id", $userID)->first();

        if (!$user) {
            $this->failedAsNotFound("user");
        }

        $file = File::query()->where("id", $fileID)->first();

        if (!$file) {
            $this->failedAsNotFound("file");
        }

        [$page, $limit] = $this->getPaginatorMetadata($request);

        $abilities = $this->fileService->getUserAbilitiesForFile(
            $file,
            $user,
            $page,
            $limit
        );

        return new AbilityCollection($abilities);
    }

    /**
     * Update user abilities for file.
     */
    public function updateUserAbilitiesForFile(
        UpdateUserAbilitiesRequest $request,
        string $fileID,
        string $userID
    ) {
        $user = User::query()->where("id", $userID)->first();
        $file = File::query()->where("id", $fileID)->first();

        if (!$file) {
            $this->failedAsNotFound("file");
        }

        $auth = User::user();

        if (!$auth->can(AbilityEnum::USER_ABILITY_MANAGE->value, $user)) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();

        try {
            $this->fileService->updateUserAbilitiesForFile($file, $user, $data);
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        return $this->succeedWithStatus();
    }
}
