<?php

declare(strict_types=1);

namespace Colossal\Utilities;

class Rfc3986
{
    /**
     * See https://www.rfc-editor.org/rfc/rfc3986#section-2.2
     */
    public const GEN_DELIMS = [
        ":", "/", "?", "#", "[", "]", "@"
    ];

    /**
     * See https://www.rfc-editor.org/rfc/rfc3986#section-2.2
     */
    public const SUB_DELIMS = [
        "!", "$", "&", "'", "(", ")", "*", "+", ",", ";", "="
    ];

    public const WHITE_SPACE = [
        " "
    ];

    /**
     * See https://www.rfc-editor.org/rfc/rfc3986#section-2.3
     */
    public const UNRESERVED = [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m",
        "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M",
        "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
        "0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
        "-", ".", "_", "~"
    ];

    /**
     * Parses a well formed URI in to its underlying components.
     *     - Scheme
     *     - Authority
     *     - Path
     *     - Query
     *     - Fragment
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#appendix-B
     *
     * @param string $uri The URI to be parsed.
     * @return array<null|string> An object containing the URI's underlying components.
     * @throws \InvalidArgumentException If $uri is not a well formed URI.
     */
    public static function parseUriIntoComponents(string $uri): array
    {
        $matches = [];
        $success = preg_match(
            "%^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?%",
            $uri,
            $matches,
            PREG_UNMATCHED_AS_NULL
        );
        if ($success) {
            $components = [];
            $components["scheme"]       = $matches[2];
            $components["authority"]    = $matches[4];
            $components["path"]         = $matches[5];
            $components["query"]        = $matches[7];
            $components["fragment"]     = $matches[9];
            return $components;
        }

        throw new \InvalidArgumentException(
            "Could not parse URI '$uri' in to components." .
            "Please check that the URI is well formed as per RFC3986."
        );
    }

    /**
     * Determines whether a string represents a valid scheme component as per RFC3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.1
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $scheme The scheme component to check.
     * @return bool Whether $scheme represents a valid scheme component.
     */
    public static function isValidScheme(string $scheme): bool
    {
        return boolval(preg_match("/^[a-z][a-z0-9+\-.]*$/", $scheme));
    }

    /**
     * Performs encoding of the scheme component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.1
     *
     * @param string $scheme The scheme component to encode.
     * @return string $scheme once it has been encoded.
     * @throws \InvalidArgumentException If $scheme is not valid as per RFC 3986.
     */
    public static function encodeScheme(string $scheme): string
    {
        if (!self::isValidScheme(strtolower($scheme))) {
            throw new \InvalidArgumentException(
                "Argument 'scheme' must start with a letter and may only contain " .
                "characters from the following set: { a-z, 0-9, +, -, .}."
            );
        }

        return strtolower($scheme);
    }

    /**
     * Performs encoding of the user info component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.1
     *
     * All percent signs are assumed to be part of a well formed percent encoding
     * already and will not be further encoded to prevent double encoding.
     *
     * @param string $userInfo The user info component to encode.
     * @return string $userInfo once it has been encoded.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $userInfo.
     *      - Any invalid percent encoded characters are found within $userInfo.
     */
    public static function encodeUserInfo(string $userInfo): string
    {
        return self::encode(
            $userInfo,
            array_merge(self::UNRESERVED, self::SUB_DELIMS, [":"])
        );
    }

    /**
     * Determines whether a string represents a valid host component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.2
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $host The host component to check.
     * @return bool Whether $host represents a valid host component.
     */
    public static function isValidHost(string $host)
    {
        if (self::isIPLiteral($host)) {
            return true;
        }

        return (
            self::isPercentEncodingValid($host) &&
            boolval(preg_match("/^[%a-zA-Z0-9\-._~!$&'()*+,;=]+$/", $host))
        );
    }

    /**
     * Performs encoding of the host component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.2
     *
     * All percent signs are assumed to be part of a well formed percent encoding
     * already and will not be further encoded to prevent double encoding.
     *
     * @param string $host The host component to encode.
     * @return string $host once it has been encoded.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $host.
     *      - Any invalid percent encoded characters are found within $host.
     */
    public static function encodeHost(string $host): string
    {
        if (self::isIPLiteral($host)) {
            return $host;
        }

        return self::encode(
            strtolower($host),
            array_merge(self::UNRESERVED, self::SUB_DELIMS)
        );
    }

    /**
     * Determines whether a string represents a valid port component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.3
     *
     * @param int|string $port The port component to check.
     * @return bool Whether $port represents a valid port component.
     */
    public static function isValidPort(int|string $port): bool
    {
        return is_int($port) || boolval(preg_match("/^[0-9]+$/", $port));
    }

    /**
     * Determines whether a string represents a valid path component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.3
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $path The path component to check.
     * @return bool Whether $path represents a valid path component.
     */
    public static function isValidPath(string $path): bool
    {
        return (
            self::isPercentEncodingValid($path) &&
            boolval(preg_match("/^[%a-zA-Z0-9\-._~!$&'()*+,;=:@\/]*$/", $path))
        );
    }

    /**
     * Determines whether a string represents a valid absolute path component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.3
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $path The path component to check.
     * @return bool Whether $path represents a valid absolute path component.
     */
    public static function isValidAbsolutePath(string $path): bool
    {
        if (!str_starts_with($path, "/") || str_starts_with($path, "//")) {
            return false;
        }

        return self::isValidPath($path);
    }

    /**
     * Performs encoding of the path component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.3
     *
     * All percent signs are assumed to be part of a well formed percent encoding
     * already and will not be further encoded to prevent double encoding.
     *
     * @param string $path The path component to encode.
     * @return string $path once it has been encoded.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $path.
     *      - Any invalid percent encoded characters are found within $path.
     */
    public static function encodePath(string $path): string
    {
        return self::encode(
            $path,
            array_merge(self::UNRESERVED, self::SUB_DELIMS, [":", "@", "/"])
        );
    }

    /**
     * Determines whether a string represents a valid query component as per RFC3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.4
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $query The query component to check.
     * @return bool Whether $query represents a valid query component.
     */
    public static function isValidQuery(string $query): bool
    {
        return (
            self::isPercentEncodingValid($query) &&
            boolval(preg_match("/^[%a-zA-Z0-9\-._~!$&'()*+,;=:@\/?]+$/", $query))
        );
    }

    /**
     * Performs encoding of the query component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.4
     *
     * All percent signs are assumed to be part of a well formed percent encoding
     * already and will not be further encoded to prevent double encoding.
     *
     * @param string $query The query component to encode.
     * @return string $query once it has been encoded.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $query.
     *      - Any invalid percent encoded characters are found within $query.
     */
    public static function encodeQuery(string $query): string
    {
        return self::encode(
            $query,
            array_merge(self::UNRESERVED, self::SUB_DELIMS, [":", "@", "/", "?"])
        );
    }

    /**
     * Determines whether a string represents a valid fragment component as per RFC3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.4
     *
     * The string is assumed to have already been percent encoded.
     *
     * @param string $fragment The fragment component to check.
     * @return bool Whether $fragment represents a valid fragment component.
     */
    public static function isValidFragment(string $fragment): bool
    {
        return (
            self::isPercentEncodingValid($fragment) &&
            boolval(preg_match("/^[%a-zA-Z0-9\-._~!$&'()*+,;=:@\/?]+$/", $fragment))
        );
    }

    /**
     * Performs encoding of the fragment component as per RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.5
     *
     * All percent signs are assumed to be part of a well formed percent encoding
     * already and will not be further encoded to prevent double encoding.
     *
     * @param string $fragment The fragment component to encode.
     * @return string $fragment once it has been encoded.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $fragment.
     *      - Any invalid percent encoded characters are found within $fragment.
     */
    public static function encodeFragment(string $fragment): string
    {
        return self::encode(
            $fragment,
            array_merge(self::UNRESERVED, self::SUB_DELIMS, [":", "@", "/", "?"])
        );
    }

    /**
     * Performs encoding as per RFC 3986.
     * https://www.rfc-editor.org/rfc/rfc3986
     *
     * For the reserved set (gen-delims and sub-delims) as well as white space:
     *      - Gen-delims => ":", "/", "?", "#", "[", "]", "@"
     *      - Sub-delims => "!", "$", "&", "'", "(", ")", "*", "+", ",", ";", "="
     * These characters are percent encoded wherever found unless they are excluded
     * marked to be ignored via the $excludedChars array.
     *
     * The provided string $str to be encoded may contain a mix of regular US-ASCII
     * characters and/or percent encoded characters. To prevent double encoding any
     * percent signs found will be assumed to already belong to a percent encoded
     * character. This means any users of this method must first percent encode any
     * percent signs
     *
     * @param string $str The string to encode (must consist of US-ASCII characters).
     * @param array<string> $excludedChars An array of strings representing characters to exclude from encoding.
     * @return string $str encoded as per RFC 3986.
     * @throws \InvalidArgumentException If:
     *      - Any non US-ASCII characters are found within $str.
     *      - Any invalid percent encoded characters are found within $str.
     */
    public static function encode(string $str, array $excludedChars = []): string
    {
        self::validateIsAscii($str);
        self::validatePercentEncoding($str);

        $reservedChars = array_merge(self::GEN_DELIMS, self::SUB_DELIMS, self::WHITE_SPACE);
        $encodingChars = array_diff($reservedChars, $excludedChars);

        $encodingCharReplacements = array_map(
            function (string $asciiChar) {
                return "%" . strtoupper(bin2hex($asciiChar));
            },
            $encodingChars
        );

        $encoded = str_replace($encodingChars, $encodingCharReplacements, $str);

        // Ensure all percent encodings are in upper case.
        $encoded = preg_replace_callback(
            "/(%[a-fA-F0-9]{2})/",
            function (array $matches): string {
                return strtoupper($matches[0]);
            },
            $encoded
        );

        if (is_array($encoded) || is_null($encoded)) {
            throw new \RuntimeException("An error occurred trying to perform percent encoding for $str.");
        }

        return $encoded;
    }

    /**
     * Validates whether a given string is US-ASCII encoded.
     * @param string $str The string to validate.
     * @throws \InvalidArgumentException if any non US-ASCII characters are found within $str.
     */
    public static function validateIsAscii(string $str): void
    {
        if (!mb_check_encoding($str, "ASCII")) {
            throw new \InvalidArgumentException("Argument 'str' must contain only US-ASCII characters.");
        }
    }

    /**
     * Validates whether all percent encodings of a given string are valid as described by RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-2.1
     *
     * @param string $str The string to validate.
     * @throws \InvalidArgumentException if any invalid percent encodings are found within $str.
     */
    public static function validatePercentEncoding(string $str): void
    {
        $matches = [];
        if (preg_match("/(%(?![a-fA-F0-9]{2}).{0,2})/", $str, $matches)) {
            throw new \InvalidArgumentException("Argument 'str' contains invalid percent encoding '$matches[0]'.");
        }
    }

    /**
     * Returns whether all percent encodings of a given string are valid as described by RFC 3986.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-2.1
     *
     * @param string $str The string to validate.
     * @return bool Whether all percent encodings found within $str are valid.
     */
    public static function isPercentEncodingValid(string $str): bool
    {
        try {
            self::validatePercentEncoding($str);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether a string represents a valid IP literal address.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.2
     *
     * @param string $str The string to check.
     * @return bool Whether $str represents a valid IP literal address.
     */
    public static function isIPLiteral(string $str): bool
    {
        $matches = [];
        if (preg_match("/^\[(.*)\]$/", $str, $matches)) {
            return self::isIPv6Address($matches[1]) || self::isIPvFutureAddress($matches[1]);
        }

        return false;
    }

    /**
     * Determines whether a string represents a valid IPv6 address.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.2
     *
     * @param string $str The string to check.
     * @return bool Whether $str represents a valid IPv6 address.
     */
    public static function isIPv6Address(string $str): bool
    {
        $hexdig = "[a-fA-F0-9]";
        $h16    = "$hexdig{1,4}";
        $ls32   = "(?:$h16:$h16)";
        $ipv6Pattern =
            "/^("                                         .
                                     "(?:$h16:){6}$ls32"  . "|" .
                                   "::(?:$h16:){5}$ls32"  . "|" .
                          "(?:$h16)?::(?:$h16:){4}$ls32"  . "|" .
            "(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32"  . "|" .
            "(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32"  . "|" .
            "(?:(?:$h16:){0,3}$h16)?::(?:$h16:){1}$ls32"  . "|" .
            "(?:(?:$h16:){0,4}$h16)?::$ls32"              . "|" .
            "(?:(?:$h16:){0,5}$h16)?::$h16"               . "|" .
            "(?:(?:$h16:){0,6}$h16)?::"                   .
            ")$/";
        return boolval(preg_match($ipv6Pattern, $str));
    }

    /**
     * Determines whether a string represents a valid IPvFuture address.
     *
     * See https://www.rfc-editor.org/rfc/rfc3986#section-3.2.2
     *
     * @param string $str The string to check.
     * @return bool Whether $str represents a valid IPvFuture address.
     */
    public static function isIPvFutureAddress(string $str): bool
    {
        $ipvFuturePattern = "/^(v[a-fA-F0-9]+\.[a-zA-Z0-9\-._~!$&'()*+,;=:]+)$/";
        return boolval(preg_match($ipvFuturePattern, $str));
    }
}
