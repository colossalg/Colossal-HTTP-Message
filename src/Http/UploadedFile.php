<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\Stream\ResourceStream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    public const UPLOAD_ERR_MAP = [
        0 => "UPLOAD_ERR_OK",
        1 => "UPLOAD_ERR_INI_SIZE",
        2 => "UPLOAD_ERR_FORM_SIZE",
        3 => "UPLOAD_ERR_PARTIAL",
        4 => "UPLOAD_ERR_NO_FILE",
        6 => "UPLOAD_ERR_NO_TMP_DIR",
        7 => "UPLOAD_ERR_CANT_WRITE",
        8 => "UPLOAD_ERR_EXTENSION"
    ];

    /**
     * Constructor.
     * @param string $filePath The file name or path for this uploaded file.
     * @param null|int $size The size of this uploaded file.
     * @param int $error The error for this uploaded file.
     * @param null|string $clientFileName The client file name for this uploaded file.
     * @param null|string $clientMediaType The client media type for this uploaded file.
     */
    public function __construct(
        string $filePath,
        null|int $size,
        int $error,
        null|string $clientFileName,
        null|string $clientMediaType
    ) {
        $this->hasMoved         = false;
        $this->filePath         = $filePath;
        $this->stream           = null;
        $this->size             = $size;
        $this->error            = $error;
        $this->clientFileName   = $clientFileName;
        $this->clientMediaType  = $clientMediaType;
    }

    /**
     * @see UploadedFileInterface::getStream()
     */
    public function getStream(): StreamInterface
    {
        $this->assertNoError();
        $this->assertHasNotMoved();

        // Lazy initialization of the stream (first opening of stream, or stream has been closed externally).
        if (is_null($this->stream) || !$this->stream->isReadable()) {
            $resource = $this->fopen($this->filePath, "r");
            if ($resource === false || !is_resource($resource)) {
                throw new \RuntimeException("Could not open file path '$this->filePath' for reading.");
            }
            $this->stream = new ResourceStream($resource);
        }

        return $this->stream;
    }

    /**
     * @see UploadedFileInterface::moveTo()
     */
    public function moveTo($targetPath): void
    {
        $this->assertNoError();
        $this->assertHasNotMoved();

        if (!is_string($targetPath)) {
            throw new \InvalidArgumentException("Argument 'targetpath' must have type string.");
        }

        if ($this->isDir($targetPath)) {
            throw new \RuntimeException("Path '$targetPath' coincides with existing directory.");
        }
        if ($this->isFile($targetPath) && !$this->isWritable($targetPath)) {
            throw new \RuntimeException("Path '$targetPath' coincides with existing unwritable file.");
        }

        if (!is_null($this->stream)) {
            $this->stream->close();
        }

        if ($this->phpSapiName() === 'cli') {
            if (!$this->rename($this->filePath, $targetPath)) {
                throw new \RuntimeException("Call to rename() returned false for '$this->filePath'.");
            }
        } else {
            if (!$this->isUploadedFile($this->filePath)) {
                throw new \RuntimeException("Call to isUploadedFile() returned false for '$this->filePath'.");
            }
            if (!$this->moveUploadedFile($this->filePath, $targetPath)) {
                throw new \RuntimeException("Call to moveUploadedFile() returned false for '$this->filePath'.");
            }
        }

        $this->hasMoved = true;
    }

    /**
     * @see UploadedFileInterface::getSize()
     */
    public function getSize(): null|int
    {
        return $this->size;
    }

    /**
     * @see UploadedFileInterface::getError()
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @see UploadedFileInterface::getClientFilename()
     */
    public function getClientFilename(): null|string
    {
        return $this->clientFileName;
    }

    /**
     * @see UploadedFileInterface::getClientMediaType()
     */
    public function getClientMediaType(): null|string
    {
        return $this->clientMediaType;
    }

    protected function fopen(string $filename, string $mode): mixed
    {
        return \fopen($filename, $mode);
    }

    protected function isDir(string $filename): bool
    {
        return \is_dir($filename);
    }

    protected function isFile(string $filename): bool
    {
        return \is_file($filename);
    }

    protected function isWritable(string $filename): bool
    {
        return \is_writable($filename);
    }

    protected function phpSapiName(): string|false
    {
        return \php_sapi_name();
    }

    protected function rename(string $oldname, string $newname): bool
    {
        return \rename($oldname, $newname);
    }

    protected function isUploadedFile(string $filename): bool
    {
        return \is_uploaded_file($filename);
    }

    protected function moveUploadedFile(string $from, string $to): bool
    {
        return \move_uploaded_file($from, $to);
    }

    private function assertNoError(): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            $errorString = self::UPLOAD_ERR_MAP[$this->error];
            throw new \RuntimeException("The uploaded file failed with error '$errorString'.");
        }
    }

    private function assertHasNotMoved(): void
    {
        if ($this->hasMoved) {
            throw new \RuntimeException("The uploaded file has been previously moved.");
        }
    }

    /**
     * @var bool Whether this uploaded file has been moved before.
     */
    private bool $hasMoved;

    /**
     * @var string The file path for this uploaded file.
     */
    private string $filePath;

    /**
     * @var null|StreamInterface The stream for this uploaded file (read-only).
     */
    private null|StreamInterface $stream;

    /**
     * @var null|int The size of this uploaded file.
     */
    private null|int $size;

    /**
     * @var int The error for this uploaded file.
     */
    private int $error;

    /**
     * @var null|string The client file name for this uploaded file.
     */
    private null|string $clientFileName;

    /**
     * @var null|string The client media type for this uploaded file.
     */
    private null|string $clientMediaType;
}
