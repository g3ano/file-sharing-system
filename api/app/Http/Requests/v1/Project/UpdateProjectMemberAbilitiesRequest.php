<?php

namespace App\Http\Requests\v1\Project;

use App\Enums\AbilityEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMemberAbilitiesRequest extends BaseRequest
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

        AbilityEnum::USER_ABILITY_MANAGE->value,
        AbilityEnum::USER_ABILITY_SPECIAL_MANAGE->value,

        AbilityEnum::STORAGE_VIEW->value,

        AbilityEnum::PROJECT_MEMBER_LIST->value,
        AbilityEnum::PROJECT_MEMBER_ADD->value,
        AbilityEnum::PROJECT_MEMBER_REMOVE->value,
        AbilityEnum::PROJECT_FILES_LIST->value,
        AbilityEnum::PROJECT_FILES_ADD->value,
        AbilityEnum::PROJECT_FILES_REMOVE->value,
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
            "remove" => ["bail", "present", "array"],
            "remove.*" => [
                "bail",
                "required",
                "string",
                Rule::in($this->validAbilities),
            ],
            "add" => ["bail", "present", "array"],
            "add.*" => [
                "bail",
                "required",
                "string",
                Rule::in($this->validAbilities),
            ],
            "forbid" => ["bail", "present", "array"],
            "forbid.*" => [
                "bail",
                "required",
                "string",
                Rule::in($this->validAbilities),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            "add.*" => "ability to add",
            "remove.*" => "ability to remove",
            "forbid.*" => "ability to to forbid",
        ];
    }
}
