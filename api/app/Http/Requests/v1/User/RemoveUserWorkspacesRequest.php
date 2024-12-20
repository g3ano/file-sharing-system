<?php

namespace App\Http\Requests\v1\User;

use App\Http\Requests\BaseRequest;
use App\Models\UserWorkspace;
use App\Models\Workspace;
use Illuminate\Validation\Rule;

class RemoveUserWorkspacesRequest extends BaseRequest
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
            "workspaces" => ["bail", "present", "array", "min:1"],
            "workspaces.*" => [
                "bail",
                "min:1",
                "numeric",
                Rule::exists(Workspace::class, "id"),
                Rule::exists(UserWorkspace::class, "workspace_id")->where(
                    "user_id",
                    $this->userID
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            "workspaces.*" => "workspace",
        ];
    }
}
