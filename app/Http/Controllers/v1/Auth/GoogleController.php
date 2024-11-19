<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver("google")->redirect();
    }

    public function googleCallbackFunction(Request $request)
    {
        try {
            $googleUser = Socialite::driver("google")->user();
        } catch (Throwable $e) {
            $this->failed(
                $this->parseExceptionError($e),
                $this->parseExceptionCode($e)
            );
        }

        $username = User::USERNAME_BASE . "_" . Str::random(8);
        $password = Str::password(12);

        $user = User::where(function ($query) use ($googleUser) {
            $query
                ->where("google_id", $googleUser->id)
                ->orWhere("email", $googleUser->email);
        })->first();

        if ($user) {
            $user->update([
                "last_name" => $googleUser->user["given_name"],
                "first_name" => $googleUser->user["family_name"],
                "username" => $username,
                "email" => $googleUser->email,
                "email_verified_at" => Carbon::now(),
                "password" => $password,
                "avatar" => $googleUser->avatar,
            ]);
        } else {
            $user = User::create([
                "last_name" => $googleUser->user["given_name"],
                "first_name" => $googleUser->user["family_name"],
                "username" => $username,
                "email" => $googleUser->email,
                "email_verified_at" => Carbon::now(),
                "password" => $password,
                "avatar" => $googleUser->avatar,
            ]);
        }

        Auth::login($user);

        return redirect(config("app.frontend_url") . "/");
    }
}
