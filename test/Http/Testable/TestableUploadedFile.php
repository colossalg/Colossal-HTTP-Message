<?php

declare(strict_types=1);

namespace Colossal\Http\Testable;

use Colossal\Utilities\NotSet;
use Psr\Http\Message\StreamInterface;

final class TestableUploadedFile extends \Colossal\Http\UploadedFile
{
    public static function createFromFile(
        string $filePath,
        null|int $size,
        int $error,
        null|string $clientFileName,
        null|string $clientMediaType
    ): self {
        $newUploadedFile = new self();
        $newUploadedFile->filePath          = $filePath;
        $newUploadedFile->size              = $size;
        $newUploadedFile->error             = $error;
        $newUploadedFile->clientFileName    = $clientFileName;
        $newUploadedFile->clientMediaType   = $clientMediaType;
        return $newUploadedFile;
    }

    public static function createFromStream(
        StreamInterface $stream,
        null|int $size,
        int $error,
        null|string $clientFileName,
        null|string $clientMediaType
    ): self {
        $newUploadedFile = new self();
        $newUploadedFile->stream            = $stream;
        $newUploadedFile->size              = $size;
        $newUploadedFile->error             = $error;
        $newUploadedFile->clientFileName    = $clientFileName;
        $newUploadedFile->clientMediaType   = $clientMediaType;
        return $newUploadedFile;
    }

    private function __construct(
        public mixed $fopenOverride = new NotSet(),
        public NotSet|bool $isDirOverride = new NotSet(),
        public NotSet|bool $isFileOverride = new NotSet(),
        public NotSet|bool $isWritableOverride = new NotSet(),
        public NotSet|string|false $phpSapiNameOverride = new NotSet(),
        public NotSet|bool $renameOverride = new NotSet(),
        public NotSet|bool $isUploadedFileOverride = new NotSet(),
        public NotSet|bool $moveUploadedFileOverride = new NotSet()
    ) {
        parent::__construct();
    }

    protected function fopen(string $filename, string $mode): mixed
    {
        if (!($this->fopenOverride instanceof NotSet)) {
            return $this->fopenOverride;
        } else {
            return parent::fopen($filename, $mode);
        }
    }

    protected function isDir(string $filename): bool
    {
        if (!($this->isDirOverride instanceof NotSet)) {
            return $this->isDirOverride;
        } else {
            return parent::isDir($filename);
        }
    }

    protected function isFile(string $filename): bool
    {
        if (!($this->isFileOverride instanceof NotSet)) {
            return $this->isFileOverride;
        } else {
            return parent::isFile($filename);
        }
    }

    protected function isWritable(string $filename): bool
    {
        if (!($this->isWritableOverride instanceof NotSet)) {
            return $this->isWritableOverride;
        } else {
            return parent::isWritable($filename);
        }
    }

    protected function phpSapiName(): string|false
    {
        if (!($this->phpSapiNameOverride instanceof NotSet)) {
            return $this->phpSapiNameOverride;
        } else {
            return parent::phpSapiName();
        }
    }

    protected function rename(string $oldname, string $newname): bool
    {
        if (!($this->renameOverride instanceof NotSet)) {
            return $this->renameOverride;
        } else {
            return parent::rename($oldname, $newname);
        }
    }

    protected function isUploadedFile(string $filename): bool
    {
        if (!($this->isUploadedFileOverride instanceof NotSet)) {
            return $this->isUploadedFileOverride;
        } else {
            return parent::isUploadedFile($filename);
        }
    }

    protected function moveUploadedFile(string $from, string $to): bool
    {
        if (!($this->moveUploadedFileOverride instanceof NotSet)) {
            return $this->moveUploadedFileOverride;
        } else {
            return parent::moveUploadedFile($from, $to);
        }
    }
}
