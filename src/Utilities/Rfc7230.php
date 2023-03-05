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
        if (
            preg_match(
                "/^(?<path>[^?]+)(?:\?(?<query>[^?]+))?$/",
                $requestTarget,
                $matches,
                PREG_UNMATCHED_AS_NULL
            )
        ) {
            $isPathOk  = !is_null($matches["path"])  && Rfc3986::isValidAbsolutePath($matches["path"]);
            $isQueryOk =  is_null($matches["query"]) || Rfc3986::isValidQuery($matches["query"]);

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
        } catch (\InvalidArgumentException) {
            return false;
        }

        $scheme     = $uriComponents["scheme"];
        $path       = $uriComponents["path"];
        $query      = $uriComponents["query"];
        $fragment   = $uriComponents["fragment"];

        // Probably should check the authority here as well.
        // See definition for heir-part:
        //      https://www.rfc-editor.org/rfc/rfc3986#section-3
        $hasValidScheme     = !is_null($scheme)     && Rfc3986::isValidScheme($scheme);
        $hasValidPath       = !is_null($path)       && Rfc3986::isValidPath($path);
        $hasValidQuery      =  is_null($query)      || Rfc3986::isValidQuery($query);
        $hasValidFragment   =  is_null($fragment);

        return $hasValidScheme && $hasValidPath && $hasValidQuery && $hasValidFragment;
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
        if (preg_match("/^(?<host>[^:]+)(?:\:(?<port>[^:]+))?$/", $requestTarget, $matches, PREG_UNMATCHED_AS_NULL)) {
            $isHostOk = !is_null($matches["host"]) && Rfc3986::isValidHost($matches["host"]);
            $isPortOk =  is_null($matches["port"]) || Rfc3986::isValidPort($matches["port"]);

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
