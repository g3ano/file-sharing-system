<?php

namespace App\Enums;

enum AbilityEnum: string
{
    //Shared
    case LIST = "list";
    case CREATE = "create";

    case VIEW = "view";
    case UPDATE = "update";
    case DELETE = "delete";
    case RESTORE = "restore";
    case FORCE_DELETE = "force delete";

    //User abilities
    case USER_WORKSPACE_LIST = "user workspace list";
    case USER_WORKSPACE_ADD = "user workspace add";
    case USER_WORKSPACE_REMOVE = "user workspace remove";
    case USER_PROJECT_LIST = "user project list";
    case USER_PROJECT_ADD = "user project add";
    case USER_PROJECT_REMOVE = "user project remove";
    case USER_ABILITY_VIEW = "user ability view";
    case USER_ABILITY_MANAGE = "user ability manage";

    //Workspace abilities
    case WORKSPACE_MEMBER_LIST = "workspace member list";
    case WORKSPACE_MEMBER_ADD = "workspace member add";
    case WORKSPACE_MEMBER_REMOVE = "workspace member remove";
    case WORKSPACE_MEMBER_ABILITY_MANAGE = "workspace member ability manage";
    case WORKSPACE_PROJECT_LIST = "workspace project list";
    case WORKSPACE_PROJECT_ADD = "workspace project add";
    case WORKSPACE_PROJECT_REMOVE = "workspace project remove";

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
            self::USER_WORKSPACE_ADD => "Add User Workspace",
            self::USER_WORKSPACE_REMOVE => "Remove User Workspace",
            self::USER_PROJECT_LIST => "List User Projects",
            self::USER_PROJECT_ADD => "Add User Project",
            self::USER_PROJECT_REMOVE => "Remove User Project",
            self::USER_ABILITY_VIEW => "View User Abilities",
            self::USER_ABILITY_MANAGE => "Manage User Abilities",
            // Workspace abilities
            self::WORKSPACE_MEMBER_LIST => "List Workspace Members",
            self::WORKSPACE_MEMBER_ADD => "Add Workspace Member",
            self::WORKSPACE_MEMBER_REMOVE => "Remove Workspace Member",
            self::WORKSPACE_MEMBER_ABILITY_MANAGE
                => "Manage Workspace Member Abilities",
            self::WORKSPACE_PROJECT_LIST => "List Workspace Projects",
            self::WORKSPACE_PROJECT_ADD => "Add Workspace Project",
            self::WORKSPACE_PROJECT_REMOVE => "Remove Workspace Project",
        };
    }
}
