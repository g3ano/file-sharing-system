<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Get current **Authenticated** user
     */
    public function getAuthUser()
    {
        return new UserResource(User::user());
    }

    public function getUserById(string $id)
    {
        $user = User::query()
            ->where('id', $id)
            ->first();

        if (!$user) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        return new UserResource($user);
    }

    public function getUserBySlug(string $slug)
    {
        $user = User::query()
            ->where('slug', $slug)
            ->first();

        if (!$user) {
            $this->failedWithMessage(__('user.not_found'), 404);
        }

        return new UserResource($user);
    }
}
