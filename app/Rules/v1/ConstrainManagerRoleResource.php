<?php

namespace App\Rules\v1;

use App\Enums\RoleEnum;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConstrainManagerRoleResource implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string = null): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $auth = User::user();

        if (
            (int) $value === RoleEnum::MANAGER->value &&
            !$auth->canDo([
                [RoleEnum::ADMIN],
                [RoleEnum::MANAGER],
            ])
        ) {
            $fail(__('validation.custom.roles.lack_permissions'));
        }
    }
}
