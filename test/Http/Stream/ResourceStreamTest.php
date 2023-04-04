<?php

declare(strict_types=1);

namespace Colossal\Http\Stream\Testing;

use Colossal\Http\Stream\ResourceStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Stream\ResourceStream
 */
final class ResourceStreamTest extends TestCase
{
    public function testCreateWithProvidedResourceThrowsForNonResourceArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ResourceStream(1); /** @phpstan-ignore-line */
    }

    public function testDestruct(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $resourceStream = new ResourceStream($resource);
        $resourceStream->write($str);
        $resourceStream->__destruct();

        // Should not be able to get contents any more.
        $this->expectException(\RuntimeException::class);
        $resourceStream->getContents();

        // Underlying resource should be closed.
        $this->assertTrue(feof($resource));
    }

    public function testDestructAfterDetach(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $resourceStream = new ResourceStream($resource);
        $resourceStream->write($str);
        $resourceStream->detach();
        $resourceStream->__destruct();

        // Should not be able to get contents any more.
        $this->expectException(\RuntimeException::class);
        $resourceStream->getContents();

        // Underlying resource should not be closed and can still be read.
        $this->assertFalse(feof($resource));
        $this->assertEquals($str, stream_get_contents($resource));
    }

    public function testToString(): void
    {
        // Test the method for a readable stream.
        $str = "Hello World!";
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write($str);
        $this->assertEquals($str, $resourceStream->__toString());
        $resourceStream->close();
        $this->assertEquals("", $resourceStream->__toString());
    }

    public function testClose(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $resourceStream = new ResourceStream($resource);
        $resourceStream->write($str);
        $this->assertEquals($str, $resourceStream->getContents());

        $resourceStream->close();

        // Should not be able to get contents any more.
        $this->expectException(\RuntimeException::class);
        $resourceStream->getContents();

        // Underlying resource should be closed.
        $this->assertTrue(feof($resource));
    }

    public function testDetach(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $resourceStream = new ResourceStream($resource);
        $resourceStream->write($str);
        $this->assertEquals($str, $resourceStream->getContents());

        $this->assertEquals($resource, $resourceStream->detach());
        $this->assertNull($resourceStream->detach());

        // Should not be able to get contents any more.
        $this->expectException(\RuntimeException::class);
        $resourceStream->getContents();

        // Underlying resource should not be closed and can still be read.
        $this->assertFalse(feof($resource));
        $this->assertEquals($str, stream_get_contents($resource));
    }

    public function testGetSize(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $resourceStream = $this->createReadWriteResourceStream();
        $this->assertEquals(0, $resourceStream->getSize());
        $resourceStream->write($str);
        $this->assertEquals(strlen($str), $resourceStream->getSize());
    }

    public function testGetSizeThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->getSize();
    }

    public function testTell(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write($str);
        $this->assertEquals(strlen($str), $resourceStream->tell());
        $resourceStream->seek(0);
        $this->assertEquals(0, $resourceStream->tell());
        $resourceStream->seek(5);
        $this->assertEquals(5, $resourceStream->tell());
    }

    public function testTellThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->tell();
    }

    public function testEof(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $resourceStream = $this->createReadWriteResourceStream();
        $this->assertFalse($resourceStream->eof());
        $resourceStream->write($str);
        $this->assertFalse($resourceStream->eof());
        $resourceStream->read(strlen($str) + 1);
        $this->assertTrue($resourceStream->eof());
        $resourceStream->rewind();
        $this->assertFalse($resourceStream->eof());
        $resourceStream->read(strlen($str) + 1);
        $this->assertTrue($resourceStream->eof());
    }

    public function testEofThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->eof();
    }

    public function testIsSeekable(): void
    {
        // Test the method for the general use case.
        $this->assertFalse($this->createReadOnlyResourceStream()->isSeekable());
        $this->assertTrue($this->createReadWriteResourceStream()->isSeekable());
    }

    public function testIsSeekableThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->isSeekable();
    }

    public function testSeek(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $this->assertNotEquals(0, $resourceStream->tell());
        $resourceStream->seek(0);
        $this->assertEquals(0, $resourceStream->tell());
        $resourceStream->seek(5);
        $this->assertEquals(5, $resourceStream->tell());
    }

    public function testSeekThrowsIfIsSeekableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not seekable.
        $this->expectException(\RuntimeException::class);
        $this->createReadOnlyResourceStream()->seek(0);
    }

    public function testRewind(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $this->assertNotEquals(0, $resourceStream->tell());
        $resourceStream->rewind();
        $this->assertEquals(0, $resourceStream->tell());
    }

    public function testIsWritable(): void
    {
        // Test that the method works for read-only, write-only and read-write resource streams.
        $this->assertFalse($this->createReadOnlyResourceStream()->isWritable());
        $this->assertTrue($this->createWriteOnlyResourceStream()->isWritable());
        $this->assertTrue($this->createReadWriteResourceStream()->isWritable());
    }

    public function testIsWritableThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->isWritable();
    }

    public function testWrite(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $this->assertEquals("Hello World!", $resourceStream->getContents());
    }

    public function testWriteThrowsIfIsWritableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not writable.
        $this->expectException(\RuntimeException::class);
        $this->createReadOnlyResourceStream()->write("10");
    }

    public function testIsReadable(): void
    {
        // Test that the method works for read-only, write-only and read-write resource streams.
        $this->assertTrue($this->createReadOnlyResourceStream()->isReadable());
        $this->assertFalse($this->createWriteOnlyResourceStream()->isReadable());
        $this->assertTrue($this->createReadWriteResourceStream()->isReadable());
    }

    public function testIsReadableThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->isReadable();
    }

    public function testRead(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $resourceStream->rewind();
        $this->assertEquals("Hello World!", $resourceStream->read(12));
        $this->assertEquals("", $resourceStream->read(12));
    }

    public function testReadThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyResourceStream()->read(10);
    }

    public function testGetContents(): void
    {
        // Test the method for the general use case.
        // (No seek necessary and multiple calls work unlike read).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $this->assertEquals("Hello World!", $resourceStream->getContents());
        $this->assertEquals("Hello World!", $resourceStream->getContents());
    }

    public function testGetContentsThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyResourceStream()->getContents();
    }

    public function testGetMetadata(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $this->assertIsArray($resourceStream->getMetadata());
        $this->assertEquals("w+b", $resourceStream->getMetadata()["mode"]);
        $this->assertEquals("w+b", $resourceStream->getMetadata("mode"));
        $this->assertNull($resourceStream->getMetadata("dummy"));
    }

    public function testGetMetadataThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectException(\RuntimeException::class);
        $resourceStream->getMetaData();
    }

    private function createReadOnlyResourceStream(): ResourceStream
    {
        // php://temp is read-write so we use stdin here in the tests for our read-only stream.
        $resource = fopen("php://stdin", "r");
        if ($resource === false) {
            throw new \RuntimeException("Failed to open php://stdin.");
        }
        return new ResourceStream($resource);
    }

    private function createWriteOnlyResourceStream(): ResourceStream
    {
        // php://temp is read-write so we use stdout here in the tests for our write-only stream.
        $resource = fopen("php://stdout", "w");
        if ($resource === false) {
            throw new \RuntimeException("Failed to open php://stdout.");
        }
        return new ResourceStream($resource);
    }

    private function createReadWriteResourceStream(): ResourceStream
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        return new ResourceStream($resource);
    }
}
