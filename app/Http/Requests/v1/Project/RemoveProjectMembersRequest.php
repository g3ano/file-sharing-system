<?php

namespace App\Http\Requests\v1\Project;

use App\Http\Requests\BaseRequest;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Validation\Rule;

class RemoveProjectMembersRequest extends BaseRequest
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
            "members" => ["bail", "present", "array", "min:1"],
            "members.*" => [
                "bail",
                "min:1",
                "numeric",
                Rule::exists(User::class, "id"),
                Rule::unique(ProjectUser::class, "user_id")->where(
                    "project_id",
                    (int) $this->projectID
                ),
            ],
        ];
    }
}
