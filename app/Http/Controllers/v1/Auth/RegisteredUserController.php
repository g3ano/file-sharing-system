<?php

namespace App\Http\Controllers\v1\Auth;

use App\Models\User;
use App\Enums\AbilityEnum;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use App\Http\Resources\v1\UserResource;
use App\Http\Requests\v1\Auth\RegisterUserRequest;
use App\Services\UserService;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        RegisterUserRequest $request,
        UserService $userService
    ): UserResource {
        $auth = User::user();

        if (!$auth || !$auth->can(AbilityEnum::CREATE->value, User::class)) {
            $this->failedAsNotFound("user");
        }

        $data = $request->validated();
        $username = User::USERNAME_BASE . "_" . Str::random(8);

        $user = User::query()->create([
            "first_name" => ucfirst($data["first_name"]),
            "last_name" => ucfirst($data["last_name"]),
            "username" => $username,
            "email" => $data["email"],
            "password" => Hash::make($data["password"]),
        ]);

        event(new Registered($user));

        $userService->getUserCapabilitiesForUser($auth, $user);

        return new UserResource($user);
    }
}
