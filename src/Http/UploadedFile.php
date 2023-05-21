<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\Stream;
use Psr\Http\Message\{ StreamInterface, UploadedFileInterface };

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
     * Create a new uploaded file from the given file path.
     * @param string $filePath The file name or path for this uploaded file.
     * @param null|int $size The size of this uploaded file.
     * @param int $error The error for this uploaded file.
     * @param null|string $clientFileName The client file name for this uploaded file.
     * @param null|string $clientMediaType The client media type for this uploaded file.
     * @return self
     */
    public static function createFromFile(
        string $filePath,
        null|int $size,
        int $error,
        null|string $clientFileName,
        null|string $clientMediaType
    ): self {
        $newUploadedFile = new UploadedFile();
        $newUploadedFile->filePath          = $filePath;
        $newUploadedFile->size              = $size;
        $newUploadedFile->error             = $error;
        $newUploadedFile->clientFileName    = $clientFileName;
        $newUploadedFile->clientMediaType   = $clientMediaType;
        return $newUploadedFile;
    }

    /**
     * Create a new uploaded file from the given stream.
     * @param StreamInterface $stream The stream for this uploaded file.
     * @param null|int $size The size of this uploaded file.
     * @param int $error The error for this uploaded file.
     * @param null|string $clientFileName The client file name for this uploaded file.
     * @param null|string $clientMediaType The client media type for this uploaded file.
     * @return self
     */
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

    protected function __construct()
    {
        $this->hasMoved         = false;
        $this->filePath         = null;
        $this->stream           = null;
        $this->size             = 0;
        $this->error            = 0;
        $this->clientFileName   = "";
        $this->clientMediaType  = "";
    }

    /**
     * @see UploadedFileInterface::getStream()
     */
    public function getStream(): StreamInterface
    {
        $this->assertNoError();
        $this->assertHasNotMoved();

        // Lazy initialization of the stream (if required).
        if (is_null($this->stream)) {
            if (is_null($this->filePath)) {
                throw new \RuntimeException("Could not open file path 'null' for reading.");
            }
            $resource = $this->fopen($this->filePath, "r");
            if ($resource === false || !is_resource($resource)) {
                throw new \RuntimeException("Could not open file path '$this->filePath' for reading.");
            }
            $this->stream = new Stream($resource);
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

        if (!is_null($this->filePath)) {
            $this->fileMoveTo($targetPath);
        } else {
            $this->streamMoveTo($targetPath);
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

    private function fileMoveTo(string $targetPath): void
    {
        if (is_null($this->filePath)) {
            throw new \RuntimeException("Call to fileMoveTo() when the filePath is null.");
        }

        if (!is_null($this->stream)) {
            $this->stream->close();
            $this->stream = null;
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
    }

    private function streamMoveTo(string $targetPath): void
    {
        if (is_null($this->stream)) {
            throw new \RuntimeException("Call to streamMoveTo() when the stream is null.");
        }
        if (!$this->stream->isReadable() || !$this->stream->isSeekable()) {
            throw new \RuntimeException("Call to streamMoveTo() when the stream is not readable and seekable.");
        }

        $resource = $this->fopen($targetPath, "w");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Could not open file path '$targetPath' for writing.");
        }
        $destStream = new Stream($resource);

        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $destStream->write($this->stream->read(100 * 1024));
        }

        $destStream->detach();

        $this->stream->close();
        $this->stream = null;
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
    protected bool $hasMoved;

    /**
     * @var null|string The file path for this uploaded file.
     */
    protected null|string $filePath;

    /**
     * @var null|StreamInterface The stream for this uploaded file (read-only).
     */
    protected null|StreamInterface $stream;

    /**
     * @var null|int The size of this uploaded file.
     */
    protected null|int $size;

    /**
     * @var int The error for this uploaded file.
     */
    protected int $error;

    /**
     * @var null|string The client file name for this uploaded file.
     */
    protected null|string $clientFileName;

    /**
     * @var null|string The client media type for this uploaded file.
     */
    protected null|string $clientMediaType;
}
