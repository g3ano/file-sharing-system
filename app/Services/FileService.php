<?php

namespace App\Services;

use App\Enums\AbilityEnum;
use App\Models\File;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Silber\Bouncer\BouncerFacade;
use Throwable;

class FileService extends BaseService
{
    /**
     * Saves file into disk, and stores its metadata in db.
     *
     * @throws RuntimeException
     */
    public function createFile(Project $project, User $user, UploadedFile $file)
    {
        try {
            DB::beginTransaction();
            [
                "path" => $path,
                "hash" => $hash,
            ] = $this->saveFile($file);

            [
                "name" => $name,
                "type" => $type,
                "extension" => $extension,
                "size" => $size,
            ] = $this->getFileData($file);

            $this->ensureUniqueFileName($project, $name);

            $metadata = $project->files()->create([
                "name" => $name,
                "extension" => $extension,
                "type" => $type,
                "size" => $size,
                "path" => $path,
                "hash" => $hash,
                "user_id" => $user->id,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Storage::delete($path);

            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }

        return $metadata;
    }

    /**
     * Get project own files.
     */
    public function getProjectFiles(
        Project $project,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = $project
            ->files()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get user own files.
     */
    public function getUserFiles(
        User $user,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = $user
            ->files()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get project own trashed files.
     */
    public function getProjectTrashedFiles(
        Project $project,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = $project
            ->files()
            ->onlyTrashed()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get project own trashed files.
     */
    public function getUserTrashedFiles(
        User $user,
        int $page = 1,
        int $limit = 10,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = $user
            ->files()
            ->onlyTrashed()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get user capabilities for against file.
     */
    public function getUserCapabilitiesForFile(User $auth, File $file)
    {
        $capabilities = [];

        $capabilities = [
            AbilityEnum::VIEW->value => $auth->can(
                AbilityEnum::VIEW->value,
                $file
            ),
            AbilityEnum::UPDATE->value => $auth->can(
                AbilityEnum::UPDATE->value,
                $file
            ),
            AbilityEnum::DELETE->value => $auth->can(
                AbilityEnum::DELETE->value,
                $file
            ),
            AbilityEnum::RESTORE->value => $auth->can(
                AbilityEnum::RESTORE->value,
                $file
            ),
            AbilityEnum::FORCE_DELETE->value => $auth->can(
                AbilityEnum::FORCE_DELETE->value,
                $file
            ),
            AbilityEnum::USER_ABILITY_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_MANAGE->value,
                $file
            ),
            AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value => $auth->can(
                AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,
                $file
            ),
            AbilityEnum::FILE_DOWNLOAD->value => $auth->can(
                AbilityEnum::FILE_DOWNLOAD->value,
                $file
            ),
        ];

        $file->capabilities = $capabilities;
    }

    /**
     * Rename a file.
     */
    public function renameFile(File $file, array $data)
    {
        return $file->update([
            "name" => $data["name"],
        ]);
    }

    /**
     * Force deletes a file.
     */
    public function forceDeleteFile(File $file)
    {
        [, $count] = $this->isDuplicateFile($file);

        if ($count < 2) {
            if (!Storage::delete($file->path)) {
                $this->failedWithMessage(
                    __("file.force_delete"),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        if (!$file->forceDelete()) {
            $this->failedWithMessage(
                __("file.force_delete"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get paginated list of files.
     */
    public function getFileList(
        int $page,
        int $limit,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = File::query()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Search files.
     */
    public function searchFileList(
        int $page,
        int $limit,
        string $searchValue,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        $searchValue = "%{$searchValue}%";
        /**
         * @var LengthAwarePaginator
         */
        $files = File::query()
            ->whereAny(
                ["name", "extension", "type", "size", "path"],
                "ILIKE",
                $searchValue
            )
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get paginated list of deleted files.
     */
    public function getDeletedFileList(
        int $page,
        int $limit,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = File::onlyTrashed()
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Search deleted files.
     */
    public function searchDeletedFileList(
        int $page,
        int $limit,
        string $searchValue,
        string $orderByField = "created_at",
        string $orderByDirection = "desc"
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $files = File::onlyTrashed()
            ->whereAny(
                ["name", "extension", "type", "size", "path"],
                "ILIKE",
                $searchValue
            )
            ->orderBy($orderByField, $orderByDirection)
            ->paginate(perPage: $limit, page: $page);

        return $files;
    }

    /**
     * Get user abilities for file.
     */
    public function getUserAbilitiesForFile(
        File $file,
        User $user,
        int $page = 1,
        int $limit = 10
    ) {
        /**
         * @var LengthAwarePaginator
         */
        $abilities = $user
            ->prepareAbilitiesBuilderFor($file)
            ->with("abilitable")
            ->paginate(perPage: $limit, page: $page);

        $abilities = $abilities->through(function ($item) {
            $this->getUserAbilityContext($item);
            return $item;
        });

        return $abilities;
    }

    /**
     * Update user abilities for file.
     *
     * @throws RuntimeException
     */
    public function updateUserAbilitiesForFile(
        File $file,
        User $user,
        array $data
    ) {
        if (empty($data)) {
            return;
        }

        [
            "add" => $abilitiesToAdd,
            "remove" => $abilitiesToRemove,
            "forbid" => $abilitiesToForbid,
        ] = $data;

        if (
            empty($abilitiesToAdd) &&
            empty($abilitiesToRemove) &&
            empty($abilitiesToForbid)
        ) {
            return;
        }

        $abilitiesToAdd = array_diff($abilitiesToAdd, $abilitiesToRemove);
        $abilitiesToAdd = array_diff($abilitiesToAdd, $abilitiesToForbid);
        $abilitiesToRemove = array_diff($abilitiesToRemove, $abilitiesToForbid);

        try {
            //Reset user given abilities for given user
            foreach (
                [$abilitiesToAdd, $abilitiesToRemove, $abilitiesToForbid]
                as $abilityGroup
            ) {
                BouncerFacade::disallow($user)->to($abilityGroup, $file);
                BouncerFacade::unforbid($user)->to($abilityGroup, $file);
            }

            if (!empty($abilitiesToAdd)) {
                BouncerFacade::allow($user)->to($abilitiesToAdd, $file);
            }

            if (!empty($abilitiesToRemove)) {
                BouncerFacade::disallow($user)->to($abilitiesToRemove, $file);
            }

            if (!empty($abilitiesToForbid)) {
                BouncerFacade::forbid($user)->to($abilitiesToForbid, $file);
            }
        } catch (Throwable $e) {
            $this->failedAtRuntime($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Saves a file into local disk.
     *
     * @throws RuntimeException
     */
    public function saveFile(UploadedFile $file)
    {
        [$existing] = $this->isDuplicateFile($file);

        if ($existing) {
            return [
                "path" => $existing->path,
                "hash" => $existing->hash,
            ];
        }

        $path = Storage::disk("local")->putFile(File::$savePath, $file);

        if (!$path) {
            $this->failedAtRuntime(
                __("file.save"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $hash = $this->hashFile($file);

        if ($hash === false) {
            $this->failedAtRuntime(
                __("file.hash"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return [
            "path" => $path,
            "hash" => $hash,
        ];
    }

    /**
     * Returns basic informations about a file.
     *
     * @throws RuntimeException
     */
    public function getFileData(UploadedFile $file)
    {
        $name = $this->sanitazeFilename($file->getClientOriginalName());
        $type = $file->getMimeType();
        $extension = $file->guessExtension();
        $size = $file->getSize(); // In bytes

        if ($size === false) {
            $this->failedAtRuntime(
                __("file.save"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return [
            "name" => $name,
            "extension" => $extension,
            "type" => $type,
            "size" => $size,
        ];
    }

    /**
     * Checks whether a given uploaded file already exists.
     */
    public function isDuplicateFile(UploadedFile|File $file)
    {
        if ($file instanceof UploadedFile) {
            $hash = $this->hashFile($file);
        } else {
            $hash = $file->hash;
        }

        if (!$hash) {
            $this->failedAtRuntime(
                __("file.hash"),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return [
            File::query()->where("hash", $hash)->first(),
            File::query()->where("hash", $hash)->count(),
        ];
    }

    /**
     * Hash given file using `sha256` algo.
     */
    public function hashFile(UploadedFile $file)
    {
        return hash_file("sha256", $file->getPathname());
    }

    /**
     * Sanitazes a filename.
     */
    public function sanitazeFilename(string $filename)
    {
        return preg_replace(
            "/[^\w\-\.]/",
            "-",
            pathinfo($filename, PATHINFO_FILENAME)
        );
    }

    /**
     * Prevent duplicate name within the project and update name
     */
    public function ensureUniqueFileName(Project $project, string &$name)
    {
        if (!$project->files()->where("name", $name)->exists()) {
            return;
        }

        $name .= "-" . Carbon::now()->timestamp;
    }
}
