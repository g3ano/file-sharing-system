<?php

namespace App\Http\Requests\v1\Project;

use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends BaseRequest
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
            "name" => [
                "bail",
                "required",
                "string",
                "max:255",
                Rule::unique(Project::class, "name"),
            ],
            "description" => ["bail", "required", "string", "max:1000"],
            "workspace_id" => [
                "bail",
                "required",
                "numeric",
                Rule::exists(Workspace::class, "id"),
            ],
        ];
    }
}
