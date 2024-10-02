<?php

namespace App\Http\Requests\v1\Auth;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['bail', 'required', 'string', 'max:255'],
            'last_name' => ['bail', 'required', 'string', 'max:255'],
            'email' => [
                'bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique(User::class, 'email'),
            ],
            // 'email' => [
            //     'bail', 'required', 'string', 'lowercase', 'email:rfc,dns', 'max:255', Rule::unique(User::class, 'email'),
            // ],
            'password' => ['bail', 'required', 'confirmed', Password::defaults()],
        ];
    }
}
