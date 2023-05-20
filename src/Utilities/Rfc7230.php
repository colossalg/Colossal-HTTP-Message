<?php

declare(strict_types=1);

namespace Colossal\Utilities;

class Rfc7230
{
    /**
     * Returns whether a request target is in valid origin-form.
     *
     * See https://www.rfc-editor.org/rfc/rfc7230#section-5.3.1
     *
     * @param string $requestTarget The request target to check.
     * @return bool Whether $requestTarget is in valid origin-form.
     */
    public static function isRequestTargetInOriginForm(string $requestTarget): bool
    {
        try {
            $components = Rfc3986::parseUriIntoComponents($requestTarget);
            if (!Rfc3986::areUriComponentsValid($components)) {
                return false;
            }

            return (
                is_null($components["scheme"])      &&
                is_null($components["user"])        &&
                is_null($components["pass"])        &&
                is_null($components["host"])        &&
                is_null($components["port"])        &&
                !is_null($components["path"])       &&
                Rfc3986::isValidAbsolutePath($components["path"]) &&
                // Query can be null or valid, nothing required here
                is_null($components["fragment"])
            );
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Returns whether a request target is in valid absolute-form.
     *
     * See https://www.rfc-editor.org/rfc/rfc7230#section-5.3.2
     *
     * @param string $requestTarget The request target to check.
     * @return bool Whether $requestTarget is in valid absolute-form.
     */
    public static function isRequestTargetInAbsoluteForm(string $requestTarget): bool
    {
        try {
            $components = Rfc3986::parseUriIntoComponents($requestTarget);
            if (!Rfc3986::areUriComponentsValid($components)) {
                return false;
            }

            return (
                !is_null($components["scheme"]) &&
                // User can be null or valid, nothing required here
                // Pass can be null or valid, nothing required here
                // Host can be null or valid, nothing required here
                // Port can be null or valid, nothing required here
                !is_null($components["path"])   &&
                // Query can be null or valid, nothing required here
                is_null($components["fragment"])
            );
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Returns whether a request target is in valid authority-form.
     *
     * See https://www.rfc-editor.org/rfc/rfc7230#section-5.3.3
     *
     * @param string $requestTarget The request target to check.
     * @return bool Whether $requestTarget is in valid authority-form.
     */
    public static function isRequestTargetInAuthorityForm(string $requestTarget): bool
    {
        try {
            $components = Rfc3986::parseAuthorityIntoComponents($requestTarget);

            return (
                is_null($components["user"]) &&
                is_null($components["pass"]) &&
                !is_null($components["host"]) && Rfc3986::isValidHost($components["host"]) &&
                !is_null($components["port"]) && Rfc3986::isValidPort($components["port"])
            );
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Returns whether a request target is in valid asterisk-form.
     *
     * See https://www.rfc-editor.org/rfc/rfc7230#section-5.3.4
     *
     * @param string $requestTarget The request target to check.
     * @return bool Whether $requestTarget is in valid asterisk-form.
     */
    public static function isRequestTargetInAsteriskForm(string $requestTarget): bool
    {
        return $requestTarget === "*";
    }
}
