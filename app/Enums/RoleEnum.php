<?php

namespace App\Enums;

enum RoleEnum: int
{
    case ADMIN = 1;
    case MANAGER = 2;
    case EDITOR = 3;
    case VIEWER = 4;

    /**
     * Get corresponding `enum` case by a `$name` if found,
     * return null if not found.
     */
    public static function fromName(string $name)
    {
        $cases = self::cases();

        foreach ($cases as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
