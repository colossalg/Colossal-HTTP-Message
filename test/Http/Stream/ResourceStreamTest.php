<?php

declare(strict_types=1);

namespace Colossal\Http\Stream;

use Colossal\PhpOverrides;
use Colossal\Http\Stream\ResourceStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Stream\ResourceStream
 */
final class ResourceStreamTest extends TestCase
{
    public const ASSERT_INVALID_MESSAGE = "Underlying resource is invalid (has been closed or detached).";

    public function setUp(): void
    {
        PhpOverrides::reset();
        $this->phpOverrides = PhpOverrides::getInstance();
    }

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
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
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
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
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
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
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
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
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

        // The method should not throw even if the resource is not valid.
        $resourceStream->close();
        $this->assertNull($resourceStream->getSize());
    }

    public function testGetSizeThrowsIfFstatFails(): void
    {
        // Test that the method throws if fstat() fails
        $this->phpOverrides->fstat = false;
        $this->expectExceptionMessage("Call to fstat() failed.");
        $this->createReadWriteResourceStream()->getSize();
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
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $resourceStream->tell();
    }

    public function testTellThrowsIfFstatFails(): void
    {
        // Test that the method throws if ftell() fails
        $this->phpOverrides->ftell = false;
        $this->expectExceptionMessage("Call to ftell() failed.");
        $this->createReadWriteResourceStream()->tell();
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

        // The method should not throw even if the resource is not valid.
        $resourceStream->rewind();
        $this->assertFalse($resourceStream->eof());
        $resourceStream->close();
        $this->assertTrue($resourceStream->eof());
    }

    public function testIsSeekable(): void
    {
        // Test the method for the general use case.
        $this->assertFalse($this->createReadOnlyResourceStream()->isSeekable());
        $this->assertFalse($this->createWriteOnlyResourceStream()->isSeekable());
        $this->assertTrue($this->createReadWriteResourceStream()->isSeekable());

        // The method should not throw even if the resource is not valid.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->assertFalse($resourceStream->isSeekable());
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

    public function testSeekThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $resourceStream->seek(0);
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

        // The method should not throw even if the resource is not valid.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->assertFalse($resourceStream->isWritable());
    }

    public function testWrite(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->write("Hello World!");
        $this->assertEquals("Hello World!", $resourceStream->getContents());
    }

    public function testWriteThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $resourceStream->write("Hello World!");
    }

    public function testWriteThrowsIfIsWritableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not writable.
        $this->expectException(\RuntimeException::class);
        $this->createReadOnlyResourceStream()->write("Hello World!");
    }

    public function testWriteThrowsIfFwriteFails(): void
    {
        // Test that the method throws if fwrite() fails
        $this->phpOverrides->fwrite = false;
        $this->expectExceptionMessage("Call to fwrite() failed.");
        $this->createReadWriteResourceStream()->write("Hello World!");
    }

    public function testIsReadable(): void
    {
        // Test that the method works for read-only, write-only and read-write resource streams.
        $this->assertTrue($this->createReadOnlyResourceStream()->isReadable());
        $this->assertFalse($this->createWriteOnlyResourceStream()->isReadable());
        $this->assertTrue($this->createReadWriteResourceStream()->isReadable());

        // The method should not throw even if the resource is not valid.
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->assertFalse($resourceStream->isReadable());
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

    public function testReadThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $resourceStream->read(10);
    }

    public function testReadThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyResourceStream()->read(10);
    }

    public function testReadThrowsIfFreadFails(): void
    {
        // Test that the method throws if fread() fails
        $this->phpOverrides->fread = false;
        $this->expectExceptionMessage("Call to fread() failed.");
        $this->createReadWriteResourceStream()->read(10);
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

    public function testGetContentsThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $resourceStream = $this->createReadWriteResourceStream();
        $resourceStream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $resourceStream->getContents();
    }

    public function testGetContentsThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyResourceStream()->getContents();
    }

    public function testGetContentsThrowsIfStreamGetContentsFails(): void
    {
        // Test that the method throws if fread() fails
        $this->phpOverrides->stream_get_contents = false;
        $this->expectExceptionMessage("Call to stream_get_contents() failed.");
        $this->createReadWriteResourceStream()->getContents();
    }

    public function testGetMetadata(): void
    {
        // Test the method for the general use case.
        $resourceStream = $this->createReadWriteResourceStream();
        $this->assertIsArray($resourceStream->getMetadata());
        $this->assertEquals("w+b", $resourceStream->getMetadata()["mode"]);
        $this->assertEquals("w+b", $resourceStream->getMetadata("mode"));
        $this->assertNull($resourceStream->getMetadata("dummy"));

        // The method should not throw even if the resource is not valid.
        $resourceStream->close();
        $this->assertNull($resourceStream->getMetadata("mode"));
        $this->assertEmpty($resourceStream->getMetadata());
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

    private PhpOverrides $phpOverrides;
}
