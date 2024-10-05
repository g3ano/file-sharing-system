<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserService;

class UserObserver
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function created(User $user): void
    {
        $this->userService->assignRegisteredUserAbilities($user);
    }
}
