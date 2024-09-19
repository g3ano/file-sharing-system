<?php

namespace App\Http\Controllers\v1\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\v1\Auth\RegisterUserRequest;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterUserRequest $request)
    {
        $data = $request->validated();
        $username = User::USERNAME_BASE . '_' . Str::random(8);
        $slug = $this->getSlug($username);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'slug' => $slug,
            'username' => $username,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return Response::noContent();
    }
}
