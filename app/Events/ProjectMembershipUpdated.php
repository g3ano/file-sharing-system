<?php

namespace App\Events;

use App\Enums\ProjectMembershipUpdatedActionEnum;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectMembershipUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $membersID;
    public int $projectID;
    public ProjectMembershipUpdatedActionEnum $action;

    /**
     * Create a new event instance.
     */
    public function __construct(
        array $membersID,
        int $projectID,
        ProjectMembershipUpdatedActionEnum $action = null
    ) {
        $this->projectID = $projectID;
        $this->membersID = (array) $membersID;
        $this->action = $action ?? ProjectMembershipUpdatedActionEnum::ADD;
    }
}
