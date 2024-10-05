<?php

namespace App\Http\Requests\v1\Workspace;

use App\Enums\AbilityEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberAbilitiesRequest extends BaseRequest
{
    /**
     * Valid abilities for request.
     */
    protected $validAbilities = [
        AbilityEnum::VIEW->value,
        AbilityEnum::UPDATE->value,
        AbilityEnum::DELETE->value,
        AbilityEnum::RESTORE->value,
        AbilityEnum::FORCE_DELETE->value,

        AbilityEnum::WORKSPACE_MEMBER_LIST->value,
        AbilityEnum::WORKSPACE_MEMBER_ADD->value,
        AbilityEnum::WORKSPACE_MEMBER_REMOVE->value,
        AbilityEnum::WORKSPACE_MEMBER_ABILITY_MANAGE->value,
        AbilityEnum::WORKSPACE_PROJECT_LIST->value,
        AbilityEnum::WORKSPACE_PROJECT_ADD->value,
        AbilityEnum::WORKSPACE_PROJECT_REMOVE->value,
    ];

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
            'remove' => ['bail', 'present', 'array'],
            'remove.*' => [
                'bail', 'required', 'string', Rule::in($this->validAbilities),
            ],
            'add' => ['bail', 'present', 'array'],
            'add.*' => [
                'bail', 'required', 'string', Rule::in($this->validAbilities),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'add.*' => 'ability to add',
            'remove.*' => 'ability to remove',
        ];
    }
}
