<?php

namespace App\Helpers;

use Illuminate\Support\Str;

trait Slugable
{
    public function getSlug(string $value): string
    {
        if (!$value) {
            return '';
        }

        return Str::slug(
            Str::trim(Str::lower(Str::squish($value)))
        );
    }
}
