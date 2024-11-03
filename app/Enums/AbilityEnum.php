<?php

namespace App\Enums;

enum AbilityEnum: string
{
    // Shared
    case LIST = "list";
    case CREATE = "create";
    case VIEW = "view";
    case UPDATE = "update";
    case DELETE = "delete";
    case RESTORE = "restore";
    case FORCE_DELETE = "force delete";

    // User abilities
    case USER_ABILITY_MANAGE = "user ability manage";
    case USER_ABILITY_SPECIAL_MANAGE = "user ability special manage";

    case USER_WORKSPACE_LIST = "user workspace list";
    case USER_PROJECT_LIST = "user project list";
    case USER_FILE_LIST = "user file list";

    case STORAGE_VIEW = "storage view";

    // Workspace abilities
    case WORKSPACE_MEMBER_LIST = "workspace member list";
    case WORKSPACE_MEMBER_ADD = "workspace member add";
    case WORKSPACE_MEMBER_REMOVE = "workspace member remove";
    case WORKSPACE_PROJECT_LIST = "workspace project list";
    case WORKSPACE_PROJECT_ADD = "workspace project add";
    case WORKSPACE_PROJECT_REMOVE = "workspace project remove";

    // Project abilities
    case PROJECT_MEMBER_LIST = "project member list";
    case PROJECT_MEMBER_ADD = "project member add";
    case PROJECT_MEMBER_REMOVE = "project member remove";
    case PROJECT_FILES_LIST = "project files list";
    case PROJECT_FILES_ADD = "project files add";
    case PROJECT_FILES_REMOVE = "project files remove";

    // Files abilities
    case FILE_DOWNLOAD = "file download";

    /**
     * Gets case label.
     */
    public function label(): string
    {
        return match ($this) {
            // Shared
            self::LIST => "List Items",
            self::CREATE => "Create Item",
            self::VIEW => "View Item",
            self::UPDATE => "Update Item",
            self::DELETE => "Delete Item",
            self::RESTORE => "Restore Item",
            self::FORCE_DELETE => "Force Delete Item",
            // User abilities
            self::USER_WORKSPACE_LIST => "List User Workspaces",
            self::USER_PROJECT_LIST => "List User Projects",
            self::USER_ABILITY_MANAGE => "Manage User Abilities",
            self::USER_ABILITY_SPECIAL_MANAGE
                => "Manage User special Abilities",
            self::STORAGE_VIEW => "View Storage status",
            // Workspace abilities
            self::WORKSPACE_MEMBER_LIST => "List Workspace Members",
            self::WORKSPACE_MEMBER_ADD => "Add Workspace Member",
            self::WORKSPACE_MEMBER_REMOVE => "Remove Workspace Member",
            self::WORKSPACE_PROJECT_LIST => "List Workspace Projects",
            self::WORKSPACE_PROJECT_ADD => "Add Workspace Project",
            self::WORKSPACE_PROJECT_REMOVE => "Remove Workspace Project",
            // Project abilities
            self::PROJECT_MEMBER_LIST => "List Project Members",
            self::PROJECT_MEMBER_ADD => "Add Project Member",
            self::PROJECT_MEMBER_REMOVE => "Remove Project Member",
            self::PROJECT_FILES_LIST => "List Project Files",
            self::PROJECT_FILES_ADD => "Add Project Files",
            self::PROJECT_FILES_REMOVE => "Remove Project Files",
            // Files abilities
            self::FILE_DOWNLOAD => "Download File",
        };
    }
}
