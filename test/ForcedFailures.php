<?php

// This file contains classes and functions which allow the forced failure of
// PHP built in functions. To do so we take advantage of how PHP resolves the
// symbols.
//
// If PHP encounters a function it will first look for a corresponding
// symbol of that name within the current namespace, if it is not found then
// it will fall back to the global namespace and search for the symbol there.
//
// We declare functions of the same name and signature as the PHP built in
// functions to do the following based upon flags set in the corresponding
// singletons:
//     1. If the flag is set to true, then mock the failure of the function.
//        (Generally means to return null or false).
//     2. Otherwise, delegate to the PHP built in function.

declare(strict_types=1);

namespace Colossal
{
    class ForcedFailuresBase
    {
        protected static null|self $instance = null;

        public static function getInstance(): static
        {
            if (is_null(self::$instance)) {
                static::reset();
            }
            return static::$instance;
        }

        public static function reset(): void
        {
            static::$instance = new static();
        }
    }
}

namespace Colossal\Http\Stream
{
    // phpcs:ignore
    final class ForcedFailures extends \Colossal\ForcedFailuresBase
    {
        public function __construct(
            public bool $fstat = false,
            public bool $ftell = false,
            public bool $fwrite = false,
            public bool $fread = false,
            public bool $stream_get_contents = false
        ) {
        }
    }

    function fstat($stream): array|false
    {
        if (ForcedFailures::getInstance()->fstat) {
            ForcedFailures::reset();
            return false;
        } else {
            return \fstat($stream);
        }
    }

    function ftell($stream): int|false
    {
        if (ForcedFailures::getInstance()->ftell) {
            ForcedFailures::reset();
            return false;
        } else {
            return \ftell($stream);
        }
    }

    function fwrite($stream, string $data, ?int $length = null): int|false
    {
        if (ForcedFailures::getInstance()->fwrite) {
            ForcedFailures::reset();
            return false;
        } else {
            return \fwrite($stream, $data, $length);
        }
    }

    function fread($stream, int $length): string|false
    {
        if (ForcedFailures::getInstance()->fread) {
            ForcedFailures::reset();
            return false;
        } else {
            return \fread($stream, $length);
        }
    }

    function stream_get_contents($stream, ?int $length = null, int $offset = -1): string|false
    {
        if (ForcedFailures::getInstance()->stream_get_contents) {
            ForcedFailures::reset();
            return false;
        } else {
            return \stream_get_contents($stream, $length, $offset);
        }
    }
}

namespace Colossal\Utilities
{
    // phpcs:ignore
    final class ForcedFailuresUtilities
    {
        public static null|self $instance = null;

        public static function getInstance(): self
        {
            if (is_null(self::$instance)) {
                self::reset();
            }
            return self::$instance;
        }

        public static function reset(): void
        {
            self::$instance = new self();
        }

        public function __construct(
            public bool $preg_match = false,
            public bool $preg_replace_callback = false
        ) {
        }
    }

    function preg_match(
        string $pattern,
        string $subject,
        array &$matches = null,
        int $flags = 0,
        int $offset = 0
    ): int|false {
        if (ForcedFailuresUtilities::getInstance()->preg_match) {
            ForcedFailuresUtilities::reset();
            return false;
        } else {
            return \preg_match(
                $pattern,
                $subject,
                $matches,
                $flags,
                $offset
            );
        }
    }

    function preg_replace_callback(
        string|array $pattern,
        callable $callback,
        string|array $subject,
        int $limit = -1,
        int &$count = null,
        int $flags = 0
    ): string|array|null {
        if (ForcedFailuresUtilities::getInstance()->preg_replace_callback) {
            ForcedFailuresUtilities::reset();
            return null;
        } else {
            return \preg_replace_callback(
                $pattern,
                $callback,
                $subject,
                $limit,
                $count,
                $flags
            );
        }
    }
}
