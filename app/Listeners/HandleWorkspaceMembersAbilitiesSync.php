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

class HandleWorkspaceMembersAbilitiesSync
{
    use HasResponse;

    /**
     * Handle the event.
     */
    public function handle(WorkspaceMembershipUpdated $event): void
    {
        $workspacesID = $event->workspacesID;
        $membersID = $event->membersID;
        $action = $event->action;

        if (empty($workspacesID) || empty($membersID)) {
            $this->failedAtRuntime(__('workspace.members.abilities_sync.empty_data', [
                'attribute' => empty($workspacesID) ? 'workspaces list' : 'members list',
            ]));
        }

        $this->BulkDeleteAbilities($membersID, $workspacesID);

        if ($action === WorkspaceMembershipUpdatedActionEnum::ADD) {
            $this->BulkInsertAbilities($membersID, $workspacesID);
        }
    }

    protected function BulkInsertAbilities(array $membersID, array $workspacesID)
    {
        $insertData = $this->getInsertData($membersID, $workspacesID);

        foreach ($insertData as $ability) {
            BouncerFacade::allow($ability['user'])
                ->to($ability['abilities'], $ability['resource']);
        }
    }

    protected function BulkDeleteAbilities(array $membersID, array $workspacesID)
    {
        $abilityIDs = $this->getWorkspacesAbilityIDs($workspacesID);
        $effected = $this->removeMembersAbilities($membersID, $abilityIDs);

        return $effected;
    }

    protected function getInsertData(array $userIDs, array $workspaceIDs)
    {
        $users = User::query()->whereIn('id', $userIDs)->get();
        $workspaces = Workspace::query()->whereIn('id', $workspaceIDs)->get();

        $defaultAbilities = [
            AbilityEnum::VIEW->value,
            AbilityEnum::WORKSPACE_MEMBER_LIST->value,
            AbilityEnum::WORKSPACE_PROJECT_LIST->value,
        ];

        $insertData = [];

        foreach ($users as $user) {
            foreach ($workspaces as $workspace) {
                $insertData[] = [
                    'user' => $user,
                    'abilities' => $defaultAbilities,
                    'resource' => $workspace,
                ];
            }
        }

        return $insertData;
    }

    /**
     * Get list of ability ids related list of workspace ids.
     */
    protected function getWorkspacesAbilityIDs(array $workspaceIDs): array
    {
        $count = 100;
        $abilityIDs = [];

        DB::table(Models::table('abilities'))
                ->select('id')
                ->where('entity_type', Workspace::class)
                ->whereIn('entity_id', $workspaceIDs)
                ->orderBy('created_at')
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
        return DB::table(Models::table('permissions'))
            ->where('entity_type', User::class)
            ->whereIn('entity_id', $userIDs)
            ->whereIn('ability_id', $abilityIDs)
            ->delete();
    }
}
