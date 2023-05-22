<?php

declare(strict_types=1);

namespace Colossal\Http\Message\Testable;

use Colossal\Http\Message\{ Stream, Utilities\NotSet };

final class TestableStream extends Stream
{
    public function __construct(
        mixed $resource,
        public NotSet|array $streamGetMetaDataOverride = new NotSet(),
        public NotSet|false|array $fstatOverride = new NotSet(),
        public NotSet|false|int $ftellOverride = new NotSet(),
        public NotSet|int $fseekOverride = new NotSet(),
        public NotSet|false|int $fwriteOverride = new NotSet(),
        public NotSet|false|string $freadOverride = new NotSet(),
        public NotSet|false|string $streamGetContentsOverride = new NotSet()
    ) {
        parent::__construct($resource);
    }

    protected function streamGetMetaData(): array
    {
        if (!($this->streamGetMetaDataOverride instanceof NotSet)) {
            return $this->streamGetMetaDataOverride;
        } else {
            return parent::streamGetMetaData();
        }
    }

    protected function fstat(): false|array
    {
        if (!($this->fstatOverride instanceof NotSet)) {
            return $this->fstatOverride;
        } else {
            return parent::fstat();
        }
    }

    protected function ftell(): false|int
    {
        if (!($this->ftellOverride instanceof NotSet)) {
            return $this->ftellOverride;
        } else {
            return parent::ftell();
        }
    }

    protected function fseek(int $offset, int $whence = SEEK_SET): int
    {
        if (!($this->fseekOverride instanceof NotSet)) {
            return $this->fseekOverride;
        } else {
            return parent::fseek($offset, $whence);
        }
    }

    protected function fwrite(string $string): false|int
    {
        if (!($this->fwriteOverride instanceof NotSet)) {
            return $this->fwriteOverride;
        } else {
            return parent::fwrite($string);
        }
    }

    protected function fread(int $length): false|string
    {
        if (!($this->freadOverride instanceof NotSet)) {
            return $this->freadOverride;
        } else {
            return parent::fread($length);
        }
    }

    protected function streamGetContents(): false|string
    {
        if (!($this->streamGetContentsOverride instanceof NotSet)) {
            return $this->streamGetContentsOverride;
        } else {
            return parent::streamGetContents();
        }
    }
}
