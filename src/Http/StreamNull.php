<?php

declare(strict_types=1);

namespace Colossal\Http;

use Psr\Http\Message\StreamInterface;

/**
 * @codeCoverageIgnore
 */
class StreamNull implements StreamInterface
{
    /**
     * @see StreamInterface::__toString()
     */
    public function __toString(): string
    {
        return "";
    }

    /**
     * @see StreamInterface::close()
     */
    public function close(): void
    {
    }

    /**
     * @see StreamInterface::detach()
     */
    public function detach(): mixed
    {
        return null;
    }

    /**
     * @see StreamInterface::getSize()
     */
    public function getSize(): null|int
    {
        return null;
    }

    /**
     * @see StreamInterface::tell()
     */
    public function tell(): int
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::eof()
     */
    public function eof(): bool
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::isSeekable()
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * @see StreamInterface::seek()
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::rewind()
     */
    public function rewind(): void
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::isWritable()
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @see StreamInterface::write()
     */
    public function write($string): int
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::isReadable()
     */
    public function isReadable(): bool
    {
        return false;
    }

    /**
     * @see StreamInterface::read()
     */
    public function read($length): string
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::getContents()
     */
    public function getContents(): string
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }

    /**
     * @see StreamInterface::getMetadata()
     */
    public function getMetadata($key = null): mixed
    {
        throw new \RuntimeException(__METHOD__ . " operation is not supported.");
    }
}
