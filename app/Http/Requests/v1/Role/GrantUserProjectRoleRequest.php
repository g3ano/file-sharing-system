<?php

namespace App\Http\Requests\v1\Role;

use App\Models\Role;
use App\Models\User;
use App\Models\Project;
use Illuminate\Validation\Rule;
use App\Http\Requests\BaseRequest;
use App\Rules\v1\UserIsProjectMember;
use App\Rules\v1\ConstrainManagerRoleResource;

class GrantUserProjectRoleRequest extends BaseRequest
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
                new UserIsProjectMember(),
            ],
            'role_id' => [
                'bail', 'required', 'numeric', Rule::exists(Role::class, 'id')->whereIn('id', Project::$validRoles), new ConstrainManagerRoleResource(),
            ],
            'context' => [
                'bail', 'array', 'nullable', 'present', 'min:2', 'max:2',
            ],
        ];
    }
}
