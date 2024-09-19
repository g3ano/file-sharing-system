<?php

namespace App\Helpers;

trait HasPath
{
    public const TEMP_PATH = 'temp';
    public const DIR_SEPARATOR = DIRECTORY_SEPARATOR;

    public function joinPath(...$paths): string
    {
        if (empty($paths)) {
            return '';
        }

        $filtered = array_map(function ($path) {
            return $this->trim($path);
        }, $paths);

        return self::DIR_SEPARATOR . implode(self::DIR_SEPARATOR, $filtered);
    }

    public function trim(string $value): string
    {
        return trim($value, "\/ \n\t\v\r\0");
    }

    public function getTempPath(string $path): string
    {
        return $this->joinPath(self::TEMP_PATH, $path);
    }


    public function getRelativePath(string $path): string
    {
        return preg_replace('~(/+((storage)|(public))/+)|/+$~', '', $path);
    }

    public function getAbsolutePath(string $path, bool $isPublic = false): string
    {
        $rootTailPath = $this->joinPath('app', $isPublic ? 'public' : '');

        return storage_path($this->joinPath(
            $rootTailPath,
            $this->trim($path)
        ));
    }
}
