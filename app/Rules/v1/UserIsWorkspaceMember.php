<?php

namespace App\Rules\v1;

use App\Enums\ResourceEnum;
use App\Models\UserWorkspace;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class UserIsWorkspaceMember implements ValidationRule, DataAwareRule
{
    protected $data;

    /**
     * Set the data under validation.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string = null): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        [
            $resource, $resourceID,
        ] = $this->data['context'];

        $userID = $this->data['user_id'];
        $resource = ResourceEnum::fromName($resource);

        if (!$resource) {
            $fail(__('validation.custom.roles.context'));
        }

        $exists = UserWorkspace::query()
            ->where('workspace_id', $resourceID)
            ->where('user_id', $userID)
            ->exists();

        if (!$exists) {
            $fail(__('validation.custom.roles.resource_member', [
                'resource' => 'workspace',
            ]));
        }
    }
}
