<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\Stream\ResourceStream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
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
        $this->assertHasNotMoved();

        // Lazy initialization of the stream (first opening of stream, or stream has been closed externally).
        if (is_null($this->stream) || !$this->stream->isReadable()) {
            $resource = fopen($this->filePath, "r");
            if ($resource === false) {
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
        $this->assertHasNotMoved();

        if (is_dir($targetPath)) {
            throw new \RuntimeException("Path '$targetPath' coincides with existing directory.");
        }
        if (is_file($targetPath) && !is_writable($targetPath)) {
            throw new \RuntimeException("Path '$targetPath' coincides with existing unwritable file.");
        }

        if (!is_null($this->stream)) {
            $this->stream->close();
        }

        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            if (!rename($this->filePath, $targetPath)) {
                throw new \RuntimeException("Call to rename() returned false for '$this->filePath'.");
            }
        } else {
            if (!is_uploaded_file($this->filePath)) {
                throw new \RuntimeException("Call to is_uploaded_file() returned false for '$this->filePath'.");
            }
            if (!move_uploaded_file($this->filePath, $targetPath)) {
                throw new \RuntimeException("Call to move_uploaded_file() returned false for '$this->filePath'.");
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