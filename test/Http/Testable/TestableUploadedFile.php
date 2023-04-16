<?php

declare(strict_types=1);

namespace Colossal\Http\Testable;

use Colossal\Utilities\NotSet;

final class TestableUploadedFile extends \Colossal\Http\UploadedFile
{
    public function __construct(
        string $filePath,
        null|int $size,
        int $error,
        null|string $clientFileName,
        null|string $clientMediaType
    ) {
        parent::__construct($filePath, $size, $error, $clientFileName, $clientMediaType);

        $this->fopenOverride            = new NotSet();
        $this->isDirOverride            = new NotSet();
        $this->isFileOverride           = new NotSet();
        $this->isWritableOverride       = new NotSet();
        $this->phpSapiNameOverride      = new NotSet();
        $this->renameOverride           = new NotSet();
        $this->isUploadedFileOverride   = new NotSet();
        $this->moveUploadedFileOverride = new NotSet();
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

    public mixed $fopenOverride;
    public NotSet|bool $isDirOverride;
    public NotSet|bool $isFileOverride;
    public NotSet|bool $isWritableOverride;
    public NotSet|string|false $phpSapiNameOverride;
    public NotSet|bool $renameOverride;
    public NotSet|bool $isUploadedFileOverride;
    public NotSet|bool $moveUploadedFileOverride;
}
