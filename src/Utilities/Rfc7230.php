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
        $matches = [];
        if (preg_match("/^([^?]+)+$/", $requestTarget, $matches, PREG_UNMATCHED_AS_NULL)) {
            $isPathOk  = !is_null($matches[1]) && Rfc3986::isValidAbsolutePath($matches[1]);
            $isQueryOk =  is_null($matches[2]) || Rfc3986::isValidQuery($matches[2]);

            return $isPathOk && $isQueryOk;
        }

        return false;
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
        $uriComponents = [];
        try {
            $uriComponents = Rfc3986::parseUriIntoComponents($requestTarget);
        } catch (\InvalidArgumentException $e) {
        }

        return is_null($uriComponents["fragment"]);
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
        $matches = [];
        if (preg_match("/^([^:]+)+$/", $requestTarget, $matches, PREG_UNMATCHED_AS_NULL)) {
            $isHostOk = !is_null($matches[1]) && Rfc3986::isValidHost($matches[1]);
            $isPortOk =  is_null($matches[2]) || Rfc3986::isValidPort($matches[2]);

            return $isHostOk && $isPortOk;
        }

        return false;
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
