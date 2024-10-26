<?php

namespace App\Listeners;

use App\Enums\AbilityEnum;
use App\Events\WorkspaceMembershipUpdated;
use App\Helpers\HasResponse;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Models;
use App\Enums\WorkspaceMembershipUpdatedActionEnum;
use App\Events\ProjectMembershipUpdated;
use App\Models\Project;

class HandleProjectMembersAbilitiesSync
{
    use HasResponse;

    /**
     * Handle the event.
     */
    public function handle(ProjectMembershipUpdated $event): void
    {
        $projectID = $event->projectID;
        $membersID = $event->membersID;
        $action = $event->action;

        if (empty($projectID) || empty($membersID)) {
            $this->failedAtRuntime(
                __("project.abilities_sync.empty_data", [
                    "attribute" => empty($projectID)
                        ? "project"
                        : "members list",
                ])
            );
        }

        $this->BulkDeleteAbilities($membersID, $projectID);

        if ($action === WorkspaceMembershipUpdatedActionEnum::ADD) {
            $this->BulkInsertAbilities($membersID, $projectID);
        }
    }

    protected function BulkInsertAbilities(array $membersID, int $projectID)
    {
        $insertData = $this->getInsertData($membersID, $projectID);

        foreach ($insertData as $ability) {
            BouncerFacade::allow($ability["user"])->to(
                $ability["abilities"],
                $ability["resource"]
            );
        }
    }

    protected function BulkDeleteAbilities(array $membersID, int $projectID)
    {
        $abilityIDs = $this->getProjectAbilityIDs($projectID);
        $effected = $this->removeMembersAbilities($membersID, $abilityIDs);

        return $effected;
    }

    protected function getInsertData(array $userIDs, int $projectID)
    {
        $users = User::query()->whereIn("id", $userIDs)->get();
        $project = Project::query()->where("id", $projectID)->first();

        $defaultAbilities = [
            AbilityEnum::VIEW->value,
            AbilityEnum::PROJECT_MEMBER_LIST->value,
            AbilityEnum::PROJECT_FILES_LIST->value,
            AbilityEnum::PROJECT_FILES_ADD->value,
            AbilityEnum::PROJECT_FILES_REMOVE->value,
        ];

        $insertData = [];

        foreach ($users as $user) {
            $insertData[] = [
                "user" => $user,
                "abilities" => $defaultAbilities,
                "resource" => $project,
            ];
        }

        return $insertData;
    }

    /**
     * Get list of ability ids related to Project.
     */
    protected function getProjectAbilityIDs(int $projectID): array
    {
        $count = 100;
        $abilityIDs = [];

        DB::table(Models::table("abilities"))
            ->select("id")
            ->where("entity_type", Workspace::class)
            ->where("entity_id", $projectID)
            ->orderBy("created_at")
            ->chunk($count, function ($abilities) use (&$abilityIDs) {
                foreach ($abilities as $ability) {
                    $abilityIDs[] = $ability->id;
                }
            });

        return $abilityIDs;
    }

    /**
     * Deletes list of abilities from list of user ids.
     */
    protected function removeMembersAbilities(array $userIDs, array $abilityIDs)
    {
        return DB::table(Models::table("permissions"))
            ->where("entity_type", User::class)
            ->whereIn("entity_id", $userIDs)
            ->whereIn("ability_id", $abilityIDs)
            ->delete();
    }
}
