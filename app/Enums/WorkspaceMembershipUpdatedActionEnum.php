<?php

namespace App\Enums;

enum WorkspaceMembershipUpdatedActionEnum: string
{
    case ADD = 'add';
    case REMOVE = 'remove';
}
