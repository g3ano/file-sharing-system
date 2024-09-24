<?php

namespace App\Http\Requests\v1\Role;

use App\Http\Requests\BaseRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

class UserIsRoleRequest extends BaseRequest
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
            'role_id' => [
                'bail', 'required', 'numeric', Rule::exists(Role::class, 'id'),
            ],
            'context' => [
                'bail', 'array', 'nullable', 'present', 'max:2',
            ],
        ];
    }
}