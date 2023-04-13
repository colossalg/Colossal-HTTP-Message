<?php

// phpcs:ignoreFile

// This file contains classes and functions which allow the overrides of the
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
//     1. If the flag is set to true, then mock the return of the function.
//     2. Otherwise, delegate to the PHP built in function.
//
// Keep in mind that while we mock the return of the function, not all of the
// side effects of the function will occur. For example, populating the $matches
// array in preg_match. This utility is predominantly useful for forcing the
// failure of methods that can't be forced to fail under usual circumstances.

declare(strict_types=1);

namespace Colossal
{
    final class NotSet
    {
    }

    final class PhpOverrides
    {
        protected static self $instance;

        public static function getInstance(): self
        {
            if (!isset(self::$instance)) {
                self::reset();
            }
            return self::$instance;
        }

        public static function setInstance(self $instance): void
        {
            self::$instance = $instance;
        }

        public static function reset(): void
        {
            self::$instance = new self();
        }

        public function __construct(
            public NotSet|bool $is_dir = new NotSet(),
            public NotSet|bool $is_file = new NotSet(),
            public NotSet|bool $is_writable = new NotSet(),
            public NotSet|bool $is_uploaded_file = new NotSet(),
            public NotSet|bool $move_uploaded_file = new NotSet(),
            public NotSet|bool $rename = new NotSet(),
            public mixed $fopen = new NotSet(),
            public NotSet|false|array $fstat = new NotSet(),
            public NotSet|false|int $ftell = new NotSet(),
            public NotSet|false|int $fwrite = new NotSet(),
            public NotSet|false|string $fread = new NotSet(),
            public NotSet|false|string $stream_get_contents = new NotSet(),
            public NotSet|false|int $preg_match = new NotSet(),
            public NotSet|null|string|array $preg_replace_callback = new NotSet(),
            public NotSet|false|string $php_sapi_name = new NotSet
        ) {

            if (!($fopen instanceof NotSet) && !is_bool($fopen) && !is_resource($fopen))
            {
                throw new \InvalidArgumentException("The argument 'fopen' must be of type NotSet|bool|resource.");
            }    
        }
    }
}

namespace Colossal\Http
{
    use Colossal\NotSet;
    use Colossal\PhpOverrides;

    function is_dir(string $filename): bool
    {
        if (!(PhpOverrides::getInstance()->is_dir instanceof NotSet)) {
            return PhpOverrides::getInstance()->is_dir;
        } else {
            return \is_dir($filename);
        }
    }

    function is_file(string $filename): bool
    {
        if (!(PhpOverrides::getInstance()->is_file instanceof NotSet)) {
            return PhpOverrides::getInstance()->is_file;
        } else {
            return \is_file($filename);
        }
    }

    function is_writable(string $filename): bool
    {
        if (!(PhpOverrides::getInstance()->is_writable instanceof NotSet)) {
            return PhpOverrides::getInstance()->is_writable;
        } else {
            return \is_writable($filename);
        }
    }

    function is_uploaded_file(string $filename): bool
    {
        if (!(PhpOverrides::getInstance()->is_uploaded_file instanceof NotSet)) {
            return PhpOverrides::getInstance()->is_uploaded_file;
        } else {
            return \is_uploaded_file($filename);
        }
    }

    function move_uploaded_file(string $from, string $to): bool
    {
        if (!(PhpOverrides::getInstance()->move_uploaded_file instanceof NotSet)) {
            return PhpOverrides::getInstance()->move_uploaded_file;
        } else {
            return \move_uploaded_file($from, $to);
        }
    }

    function rename(string $from, string $to): bool
    {
        if (!(PhpOverrides::getInstance()->rename instanceof NotSet)) {
            return PhpOverrides::getInstance()->rename;
        } else {
            return \rename($from, $to);
        }
    }

    function fopen(string $filename, string $mode): mixed
    {
        if (!(PhpOverrides::getInstance()->fopen instanceof NotSet)) {
            return PhpOverrides::getInstance()->fopen;
        } else {
            return \fopen($filename, $mode);
        }
    }

    function php_sapi_name(): false|string
    {
        if (!(PhpOverrides::getInstance()->php_sapi_name instanceof NotSet)) {
            return PhpOverrides::getInstance()->php_sapi_name;
        } else {
            return \php_sapi_name();
        }
    }
}

namespace Colossal\Http\Stream
{
    use Colossal\NotSet;
    use Colossal\PhpOverrides;

    function fstat($stream): array|false
    {
        if (!(PhpOverrides::getInstance()->fstat instanceof NotSet)) {
            return PhpOverrides::getInstance()->fstat;
        } else {
            return \fstat($stream);
        }
    }

    function ftell($stream): int|false
    {
        if (!(PhpOverrides::getInstance()->ftell instanceof NotSet)) {
            return PhpOverrides::getInstance()->ftell;
        } else {
            return \ftell($stream);
        }
    }

    function fwrite($stream, string $data, ?int $length = null): int|false
    {
        if (!(PhpOverrides::getInstance()->fwrite instanceof NotSet)) {
            return PhpOverrides::getInstance()->fwrite;
        } else {
            return \fwrite($stream, $data, $length);
        }
    }

    function fread($stream, int $length): string|false
    {
        if (!(PhpOverrides::getInstance()->fread instanceof NotSet)) {
            return PhpOverrides::getInstance()->fread;
        } else {
            return \fread($stream, $length);
        }
    }

    function stream_get_contents($stream, ?int $length = null, int $offset = -1): string|false
    {
        if (!(PhpOverrides::getInstance()->stream_get_contents instanceof NotSet)) {
            return PhpOverrides::getInstance()->stream_get_contents;
        } else {
            return \stream_get_contents($stream, $length, $offset);
        }
    }
}

namespace Colossal\Utilities
{
    use Colossal\NotSet;
    use Colossal\PhpOverrides;

    function preg_match(
        string $pattern,
        string $subject,
        array &$matches = null,
        int $flags = 0,
        int $offset = 0
    ): int|false {
        if (!(PhpOverrides::getInstance()->preg_match instanceof NotSet)) {
            return PhpOverrides::getInstance()->preg_match;
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
        if (!(PhpOverrides::getInstance()->preg_replace_callback instanceof NotSet)) {
            return PhpOverrides::getInstance()->preg_replace_callback;
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
