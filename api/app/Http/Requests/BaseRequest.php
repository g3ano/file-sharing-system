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
        $this->merge($this->formatPreValidation($this->all()));
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();
        $result = [];

        foreach ($errors as $column => $errorsArr) {
            $isNested = str_contains($column, ".");

            $column = preg_replace_callback(
                "/(_)(.)/",
                function ($groups) {
                    return strtoupper($groups[2]);
                },
                $column
            );

            if (!$isNested) {
                $result[$column] = $errorsArr[0];
                continue;
            }

            $columnArr = explode(".", $column);

            $result = $this->nestArr($columnArr, $errorsArr[0]);
        }

        $this->failed($result, 422);
    }

    /**
     * Spread Laravel's dot separated key into nested array,
     * and fill empty elements with `null`.
     */
    private function nestArr(array $arr, string $message): array|string
    {
        if (empty($arr)) {
            return $message;
        }

        $key = array_shift($arr);

        if (is_numeric($key)) {
            $result = [];

            //Fill up valid inputs with null up to index
            //of the errored value.
            for ($i = 0; $i < min((int) $key, 50); $i++) {
                $result[] = null;
            }

            $result[] = empty($arr) ? $message : $this->nestArr($arr, $message);

            return $result;
        }

        return [
            $key => $this->nestArr($arr, $message),
        ];
    }

    /**
     * Transforms the given data before they get validated.
     */
    public function formatPreValidation(array $arr): array
    {
        $result = [];

        foreach ($arr as $column => $value) {
            $key = strtolower(preg_replace("/([A-Z])/", '_$0', $column));
            $result[$key] = is_array($value)
                ? $this->formatPreValidation($value)
                : $value;
        }

        return $result;
    }
}
