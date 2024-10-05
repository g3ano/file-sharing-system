<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Enums\WorkspaceMembershipUpdatedActionEnum;

class WorkspaceMembershipUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $membersID;
    public array $workspacesID;
    public WorkspaceMembershipUpdatedActionEnum $action;

    /**
     * Create a new event instance.
     */
    public function __construct(int|array $membersID = [], int|array $workspacesID = [], ?WorkspaceMembershipUpdatedActionEnum $action = null)
    {
        $this->workspacesID = (array) $workspacesID;
        $this->membersID = (array) $membersID;
        $this->action = $action ?? WorkspaceMembershipUpdatedActionEnum::ADD;
    }
}
