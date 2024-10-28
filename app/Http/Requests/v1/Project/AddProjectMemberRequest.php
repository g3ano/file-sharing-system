<?php

namespace App\Http\Requests\v1\Project;

use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserWorkspace;
use Illuminate\Validation\Rule;

class AddProjectMemberRequest extends BaseRequest
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
        $project = once(
            fn() => Project::query()
                ->where("id", (int) $this->projectID)
                ->first()
        );

        // throw new RuntimeException(json_encode((int) $this->projectID));

        return [
            "members" => ["bail", "present", "array", "min:1"],
            "members.*" => [
                "bail",
                "min:1",
                "numeric",
                Rule::exists(User::class, "id"),
                Rule::when(
                    !is_null($project),
                    Rule::exists(UserWorkspace::class, "user_id")->where(
                        "workspace_id",
                        $project?->workspace_id
                    )
                ),
                Rule::unique(ProjectUser::class, "user_id")->where(
                    "project_id",
                    (int) $this->projectID
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            "members.*" => "member",
        ];
    }
}
