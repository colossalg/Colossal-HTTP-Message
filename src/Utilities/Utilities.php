<?php

declare(strict_types=1);

namespace Colossal\Utilities;

class Utilities
{
    /**
     * This function checks whether $value a string or an array of strings.
     * @param mixed $value The value to check.
     * @return bool Whether $value is a string or an array of strings.
     */
    public static function isStringOrArrayOfStrings(mixed $value): bool
    {
        if (is_string($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $val) {
                if (!is_string($val)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
