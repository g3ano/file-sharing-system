<?php

namespace App\Http\Requests\v1\Workspace;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Models\UserWorkspace;
use Illuminate\Validation\Rule;

class AddWorkspaceMembersRequest extends BaseRequest
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
                Rule::unique(UserWorkspace::class, "user_id")->where(
                    "workspace_id",
                    $this->workspaceID
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
