<?php

namespace App\Http\Requests\v1\User;

use App\Enums\AbilityEnum;
use App\Enums\ResourceEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateUserGlobalAbilitiesRequest extends BaseRequest
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
            'add' => ['bail', 'present', 'array'],
            'add.*' => ['bail', 'present', 'array'],
            'add.*.name' => ['bail', 'required', Rule::enum(AbilityEnum::class)],
            'add.*.type' => ['bail', 'required', Rule::enum(ResourceEnum::class)],
            'remove' => ['bail', 'present', 'array'],
            'remove.*' => ['bail', 'present', 'array'],
            'remove.*.name' => ['bail', 'required', Rule::enum(AbilityEnum::class)],
            'remove.*.type' => ['bail', 'required', Rule::enum(ResourceEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'add.*' => 'entries to add',
            'add.*.name' => 'name',
            'add.*.type' => 'type',
            'remove.*' => 'entries to remove',
            'remove.*.name' => 'name',
            'remove.*.type' => 'type',
        ];
    }
}
