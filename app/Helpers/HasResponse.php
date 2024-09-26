<?php

namespace App\Helpers;

use Illuminate\Http\Exceptions\HttpResponseException;
use RuntimeException;
use Throwable;

trait HasResponse
{
    public function succeed(array $data, int $status = 200, $wrap = true, $headers = [])
    {
        return response()->json(
            $wrap
                ? ['data' => $data]
                : $data,
            $status,
            $headers
        );
    }

    public function succeedWithPagination(array $data = [], $page = 1, $pages = 1, int $status = 200, $headers = [])
    {
        return $this->succeed([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
            ],
        ], $status, false, $headers);
    }

    public function succeedWithStatus(array $data = [], int $status = 200, array $headers = [])
    {
        return $this->succeed([
            'status' => 'success',
            ...$data,
        ], $status, true, $headers);
    }

    public function succeedWithMessage(string $message, array $data = [], int $status = 200, array $headers = [])
    {
        return $this->succeed([
            'message' => $message,
            ...$data,
        ], $status, true, $headers);
    }

    /**
     * @throws HttpResponseException
     */
    public function failed(array $errors, int $status = 400, array $headers = [])
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $errors,
            ], $status, $headers)
        );
    }

    /**
     * @throws HttpResponseException
     */
    public function failedWithMessage(string $message, int $status = 400, array $headers = [])
    {
        $this->failed([
            'status' => $status,
            'message' => $message,
        ], $status, $headers);
    }

    public function failedAsNotFound(?string $resource = null)
    {
        $message = $resource
            ? __("$resource.not_found")
            : 'Resource is not found';

        $this->failedWithMessage($message, 404);
    }

    /**
     * @throws RuntimeException
     */
    public static function failedAtRuntime(string $error, ?int $code = 400, ?string $key = null)
    {
        $error = is_null($key) ? $error : $key . '|' . $error;

        throw new RuntimeException($error, $code);
    }

    /**
     * Get an (optional) array from a `|` separated string error message,
     * where the first value represent the field name, and the second one
     * is its value.
     */
    public function parseExceptionError(Throwable $e): array
    {
        $error = $e->getMessage();

        if (!$error) {
            return [
                'message' => '',
            ];
        }

        if (!str_contains($error, '|')) {
            return [
                'message' => $error,
            ];
        }
        $errors = explode('|', $error);

        return [
            $errors[0] => $errors[1],
        ];
    }

    public function parseExceptionCode(Throwable $e)
    {
        $code = $e->getCode();

        if (is_null($code)) {
            return 400;
        }

        return strlen($code) !== 3 ? 400 : $code;
    }
}
