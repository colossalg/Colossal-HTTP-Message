<?php declare(strict_types=1);

namespace Colossal\Utilities;

class Utilities
{
    /**
     * This function performs a deep copy of a given array.
     * 
     * Curtesy of Andre Larsson from this Stack Overflow thread:
     * https://stackoverflow.com/questions/1532618/is-there-a-function-to-make-a-copy-of-a-php-array-to-another
     * 
     * @param array $array The array to copy.
     * @return array A copy of the array.
     */
    static function arrayClone(array $array): array
    {
        return array_map(function($element) {
            return ((is_array($element))
                ? self::arrayClone($element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            );
        }, $array);
    }

    /**
     * This function checks whether $value a string or an array of strings.
     * @param mixed $value The value to check.
     * @return bool Whether $value is a string or an array of strings.
     */
    static function isStringOrArrayOfStrings(mixed $value): bool
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