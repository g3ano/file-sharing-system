<?php

namespace App\Http\Requests\v1\File;

use App\Http\Requests\BaseRequest;
use App\Models\File;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CreateFileRequest extends BaseRequest
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
            "project_id" => [
                "bail",
                "required",
                "numeric",
                Rule::exists(Project::class, '"id'),
            ],
            "file" => [
                "bail",
                "required",
                "file",
                "max:" . File::$maxFileSize,
                "mimes:" . Arr::join(File::$allowedFileTypes, ","),
            ],
        ];
    }
}
