<?php

namespace App\Http\Requests\v1\User;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseRequest
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
            "first_name" => ["bail", "required", "string", "max:255"],
            "last_name" => ["bail", "required", "string", "max:255"],
            "email" => [
                "bail",
                "required",
                "string",
                "lowercase",
                "email:rfc",
                "max:255",
                Rule::unique(User::class, "email")->ignore($this->userID, "id"),
            ],
            // 'email' => [
            //     'bail', 'required', 'string', 'lowercase', 'email:rfc,dns', 'max:255', Rule::unique(User::class, 'email'),
            // ],
            "password" => [
                "bail",
                "nullable",
                "confirmed",
                Password::defaults(),
            ],
        ];
    }
}
