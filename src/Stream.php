<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
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
     * @param null|resource $resource The provided underlying resource.
     */
    public function __construct($resource)
    {
        if (!(is_null($resource) || is_resource($resource))) {
            throw new \InvalidArgumentException("Argument 'resource' must have type resource.");
        }

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
        if (is_null($this->resource)) {
            return;
        }

        $tmpResource    = $this->resource;
        $this->resource = null;

        fclose($tmpResource);
    }

    /**
     * @see StreamInterface::detach()
     */
    public function detach(): mixed
    {
        if (is_null($this->resource)) {
            return null;
        }

        $tmpResource    = $this->resource;
        $this->resource = null;

        return $tmpResource;
    }

    /**
     * @see StreamInterface::getSize()
     */
    public function getSize(): null|int
    {
        if (is_null($this->resource)) {
            return null;
        }

        $res = $this->fstat();
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

        $res = $this->ftell();
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
        if (is_null($this->resource)) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * @see StreamInterface::isSeekable()
     */
    public function isSeekable(): bool
    {
        if (is_null($this->resource)) {
            return false;
        }

        return boolval($this->getMetadata("seekable"));
    }

    /**
     * @see StreamInterface::seek()
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->assertValid();

        if (!$this->isSeekable()) {
            throw new \RuntimeException("Underlying resource is not seekable.");
        }

        $res = $this->fseek($offset, $whence);
        if ($res === -1) {
            throw new \RuntimeException("Call to fseek() failed.");
        }
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
        if (is_null($this->resource)) {
            return false;
        }

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
    public function write(string $string): int
    {
        $this->assertValid();

        if (!$this->isWritable()) {
            throw new \RuntimeException("Underlying resource is not writable.");
        }

        $res = $this->fwrite($string);
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
        if (is_null($this->resource)) {
            return false;
        }

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
    public function read(int $length): string
    {
        $this->assertValid();

        if (!$this->isReadable()) {
            throw new \RuntimeException("Underlying resource is not readable.");
        }

        $res = $this->fread($length);
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
        $this->assertValid();

        if (!$this->isReadable()) {
            throw new \RuntimeException("Underlying resource is not readable.");
        }

        $res = $this->streamGetContents();
        if ($res === false) {
            throw new \RuntimeException("Call to streamGetContents() failed.");
        }

        return $res;
    }

    /**
     * @see StreamInterface::getMetadata()
     */
    public function getMetadata(null|string $key = null): mixed
    {
        if (is_null($this->resource)) {
            return (is_null($key) ? [] : null);
        }

        $metadata = $this->streamGetMetaData();
        if (!is_null($key)) {
            return (isset($metadata[$key]) ? $metadata[$key] : null);
        }

        return $metadata;
    }

    protected function streamGetMetaData(): array
    {
        return \stream_get_meta_data($this->resource); // @phpstan-ignore-line
    }

    protected function fstat(): false|array
    {
        return \fstat($this->resource); // @phpstan-ignore-line
    }

    protected function ftell(): false|int
    {
        return \ftell($this->resource); // @phpstan-ignore-line
    }

    protected function fseek(int $offset, int $whence = SEEK_SET): int
    {
        return \fseek($this->resource, $offset, $whence); // @phpstan-ignore-line
    }

    protected function fwrite(string $string): false|int
    {
        return \fwrite($this->resource, $string); // @phpstan-ignore-line
    }

    protected function fread(int $length): false|string
    {
        return \fread($this->resource, max(0, $length)); // @phpstan-ignore-line
    }

    protected function streamGetContents(): false|string
    {
        return \stream_get_contents($this->resource, offset: 0); // @phpstan-ignore-line
    }

    private function assertValid(): void
    {
        if (is_null($this->resource)) {
            throw new \RuntimeException("Underlying resource is invalid (is null, has been closed or is detached).");
        }
    }

    /**
     * @var null|resource The underlying resource for this stream (generally php://input or php://temp).
     */
    private $resource;
}
