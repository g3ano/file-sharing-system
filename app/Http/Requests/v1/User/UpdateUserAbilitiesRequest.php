<?php

namespace App\Http\Requests\v1\User;

use App\Enums\AbilityEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateUserAbilitiesRequest extends BaseRequest
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
        AbilityEnum::USER_WORKSPACE_LIST->value,
        AbilityEnum::USER_WORKSPACE_ADD->value,
        AbilityEnum::USER_WORKSPACE_REMOVE->value,
        AbilityEnum::USER_PROJECT_LIST->value,
        AbilityEnum::USER_PROJECT_ADD->value,
        AbilityEnum::USER_PROJECT_REMOVE->value,
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
            "add.*" => "entries to add",
            "add.*.name" => "name",
            "add.*.type" => "type",
            "remove.*" => "entries to remove",
            "remove.*.name" => "name",
            "remove.*.type" => "type",
            "forbid.*" => "entries to forbid",
            "forbid.*.name" => "name",
            "forbid.*.type" => "type",
        ];
    }
}
