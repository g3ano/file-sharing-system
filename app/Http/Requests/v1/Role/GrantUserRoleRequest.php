<?php

namespace App\Http\Requests\v1\Role;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest;

class GrantUserRoleRequest extends BaseRequest
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
            'user_id' => [
                'bail', 'required', 'numeric', Rule::exists(User::class, 'id'),
            ],
            'role_id' => [
                'bail', 'required', 'numeric', Rule::in(Role::$validGlobalRoles),
            ],
            'context' => [
                'bail', 'array', 'nullable', 'present', 'min:2', 'max:2',
            ],
        ];
    }
}
