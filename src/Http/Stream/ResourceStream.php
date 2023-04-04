<?php

declare(strict_types=1);

namespace Colossal\Http\Stream;

use Psr\Http\Message\StreamInterface;

class ResourceStream implements StreamInterface
{
    public const READ_ONLY_MODES    = [
        "r", "rb"
    ];
    public const WRITE_ONLY_MODES   = [
        "w", "wb",
        "a", "ab",
        "x", "xb",
        "c", "cb"
    ];
    public const READ_WRITE_MODES   = [
        "r+", "r+b",
        "w+", "w+b",
        "a+", "a+b",
        "x+", "x+b",
        "c+", "c+b"
    ];

    /**
     * Constructor.
     * @param resource $resource The provided underlying resource.
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException("Argument 'resource' must have type resource.");
        }

        $this->valid    = true;
        $this->resource = $resource;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @see StreamInterface::__toString()
     */
    public function __toString(): string
    {
        $res = "";
        try {
            $this->rewind();
            $res = $this->getContents();
        } catch (\RuntimeException) {
            $res = "";
        }
        return $res;
    }

    /**
     * @see StreamInterface::close()
     */
    public function close(): void
    {
        if (!$this->valid) {
            return;
        }

        $this->valid = false;
        fclose($this->resource);
    }

    /**
     * @see StreamInterface::detach()
     */
    public function detach(): mixed
    {
        if (!$this->valid) {
            return null;
        }

        $this->valid = false;
        return $this->resource;
    }

    /**
     * @see StreamInterface::getSize()
     */
    public function getSize(): null|int
    {
        $this->assertValid();

        $res = fstat($this->resource);
        if ($res === false) {
            throw new \RuntimeException("Call to fstat() failed.");
        }

        return $res["size"];
    }

    /**
     * @see StreamInterface::tell()
     */
    public function tell(): int
    {
        $this->assertValid();

        $res = ftell($this->resource);
        if ($res === false) {
            throw new \RuntimeException("Call to ftell() failed.");
        }

        return $res;
    }

    /**
     * @see StreamInterface::eof()
     */
    public function eof(): bool
    {
        $this->assertValid();

        return feof($this->resource);
    }

    /**
     * @see StreamInterface::isSeekable()
     */
    public function isSeekable(): bool
    {
        $this->assertValid();

        return boolval($this->getMetadata("seekable"));
    }

    /**
     * @see StreamInterface::seek()
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException("Underlying resource is not seekable.");
        }

        fseek($this->resource, $offset, $whence);
    }

    /**
     * @see StreamInterface::rewind()
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @see StreamInterface::isWritable()
     */
    public function isWritable(): bool
    {
        $this->assertValid();

        $mode = $this->getMetadata("mode");
        return (
            !is_null($mode) && (
                array_search($mode, static::WRITE_ONLY_MODES) !== false ||
                array_search($mode, static::READ_WRITE_MODES) !== false
            )
        );
    }

    /**
     * @see StreamInterface::write()
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException("Underlying resource is not writable.");
        }

        $res = fwrite($this->resource, $string);
        if ($res === false) {
            throw new \RuntimeException("Call to fwrite() failed.");
        }

        return $res;
    }

    /**
     * @see StreamInterface::isReadable()
     */
    public function isReadable(): bool
    {
        $this->assertValid();

        $mode = $this->getMetadata("mode");
        return (
            !is_null($mode) && (
                array_search($mode, static::READ_ONLY_MODES)  !== false ||
                array_search($mode, static::READ_WRITE_MODES) !== false
            )
        );
    }

    /**
     * @see StreamInterface::read()
     */
    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Underlying resource is not readable.");
        }

        $res = fread($this->resource, max(0, $length));
        if ($res === false) {
            throw new \RuntimeException("Call to fread() failed.");
        }

        return $res;
    }

    /**
     * @see StreamInterface::getContents()
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Underlying resource is not readable.");
        }

        $res = stream_get_contents($this->resource, offset: 0);
        if ($res === false) {
            throw new \RuntimeException("Call to stream_get_contents() failed.");
        }

        return $res;
    }

    /**
     * @see StreamInterface::getMetadata()
     */
    public function getMetadata($key = null): mixed
    {
        $this->assertValid();

        $metadata = stream_get_meta_data($this->resource);
        if (!is_null($key)) {
            return (isset($metadata[$key]) ? $metadata[$key] : null);
        }

        return $metadata;
    }

    private function assertValid(): void
    {
        if (!$this->valid) {
            throw new \RuntimeException("Underlying resource is invalid (has been closed or detached).");
        }
    }

    /**
     * @var bool Whether the underlying resource for this stream is valid (has not been closed or detached).
     */
    private bool $valid;

    /**
     * @var resource The underlying resource for this stream (generally php://input or php://temp).
     */
    private $resource;
}
