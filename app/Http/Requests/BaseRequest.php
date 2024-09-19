<?php

namespace App\Http\Requests;

use App\Helpers\HasResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    use HasResponse;

    protected $stopOnFirstFailure = true;

    protected function prepareForValidation()
    {
        $this->merge(
            $this->formatPreValidation($this->all())
        );
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();

        $result = [];
        foreach ($errors as $column => $errorsArr) {
            $isNested = str_contains($column, '.');

            $column = preg_replace_callback('/(_)(.)/', function ($groups) {
                return strtoupper($groups[2]);
            }, $column);

            if (!$isNested) {
                $result[$column] = $errorsArr[0];
                continue;
            }

            $columnArr = explode('.', $column);
            $result = array_merge(
                $result,
                $this->nestArr($columnArr, $errorsArr[0])
            );
        }

        $this->failed($result, 422);
    }

    private function nestArr(array $arr, string $message)
    {
        if (empty($arr)) {
            return [];
        }

        $key = array_shift($arr);

        if (empty($arr)) {
            return [
                $key => $message,
            ];
        }
        return [
            $key => $this->nestArr($arr, $message),
        ];
    }

    /**
     * Transforms the given data before they get validated
     *
     * @return array
     */
    public function formatPreValidation(array $arr)
    {
        $result = [];

        foreach ($arr as $column => $value) {
            $key = strtolower(preg_replace('/([A-Z])/', '_$0', $column));
            $result[$key] = $value;
        }

        return $result;
    }
}
