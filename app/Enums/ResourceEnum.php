<?php

namespace App\Enums;

enum ResourceEnum: string
{
    case USER = 'USER';
    case WORKSPACE = 'WORKSPACE';
    case PROJECT = 'PROJECT';

    /**
     * Get corresponding `enum` case by a `$name` if found,
     * return null if not found.
     */
    public static function fromName(?string $name): ?static
    {
        if (is_null($name)) {
            return null;
        }

        $cases = self::cases();

        foreach ($cases as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
