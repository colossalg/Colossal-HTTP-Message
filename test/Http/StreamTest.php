<?php

declare(strict_types=1);

namespace Colossal\Http\Stream;

use Colossal\Http\Stream;
use Colossal\Http\Testable\TestableStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Stream
 */
final class StreamTest extends TestCase
{
    public const ASSERT_INVALID_MESSAGE = "Underlying resource is invalid (is null, has been closed or is detached).";

    public function testCreateWithProvidedResourceThrowsForNonNullOrResourceArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream(1); /** @phpstan-ignore-line */
    }

    public function testDestruct(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $stream = new Stream($resource);
        $stream->write($str);
        $stream->__destruct();

        // Should not be able to get contents any more.
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->getContents();

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
        $stream = new Stream($resource);
        $stream->write($str);
        $stream->detach();
        $stream->__destruct();

        // Should not be able to get contents any more.
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->getContents();

        // Underlying resource should not be closed and can still be read.
        $this->assertFalse(feof($resource));
        $this->assertEquals($str, stream_get_contents($resource));
    }

    public function testToString(): void
    {
        // Test the method for a readable stream.
        $str = "Hello World!";
        $stream = $this->createReadWriteStream();
        $stream->write($str);
        $this->assertEquals($str, $stream->__toString());
        $stream->close();
        $this->assertEquals("", $stream->__toString());
    }

    public function testClose(): void
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $str = "Hello World!";
        $stream = new Stream($resource);
        $stream->write($str);
        $this->assertEquals($str, $stream->getContents());

        $stream->close();

        // Should not be able to get contents any more.
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->getContents();

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
        $stream = new Stream($resource);
        $stream->write($str);
        $this->assertEquals($str, $stream->getContents());

        $this->assertEquals($resource, $stream->detach());
        $this->assertNull($stream->detach());

        // Should not be able to get contents any more.
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->getContents();

        // Underlying resource should not be closed and can still be read.
        $this->assertFalse(feof($resource));
        $this->assertEquals($str, stream_get_contents($resource));
    }

    public function testGetSize(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $stream = $this->createReadWriteStream();
        $this->assertEquals(0, $stream->getSize());
        $stream->write($str);
        $this->assertEquals(strlen($str), $stream->getSize());

        // The method should not throw even if the resource is not valid.
        $stream->close();
        $this->assertNull($stream->getSize());
    }

    public function testGetSizeThrowsIfFstatFails(): void
    {
        // Test that the method throws if fstat() fails
        $this->expectExceptionMessage("Call to fstat() failed.");
        $stream = $this->createReadWriteStream();
        $stream->fstatOverride = false;
        $stream->getSize();
    }

    public function testTell(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $stream = $this->createReadWriteStream();
        $stream->write($str);
        $this->assertEquals(strlen($str), $stream->tell());
        $stream->seek(0);
        $this->assertEquals(0, $stream->tell());
        $stream->seek(5);
        $this->assertEquals(5, $stream->tell());
    }

    public function testTellThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->tell();
    }

    public function testTellThrowsIfFtellFails(): void
    {
        // Test that the method throws if ftell() fails
        $this->expectExceptionMessage("Call to ftell() failed.");
        $stream = $this->createReadWriteStream();
        $stream->ftellOverride = false;
        $stream->tell();
    }

    public function testEof(): void
    {
        // Test the method for the general use case.
        $str = "Hello World!";
        $stream = $this->createReadWriteStream();
        $this->assertFalse($stream->eof());
        $stream->write($str);
        $this->assertFalse($stream->eof());
        $stream->read(strlen($str) + 1);
        $this->assertTrue($stream->eof());
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $stream->read(strlen($str) + 1);
        $this->assertTrue($stream->eof());

        // The method should not throw even if the resource is not valid.
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $stream->close();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekable(): void
    {
        $stream = $this->createReadWriteStream();
        $stream->streamGetMetaDataOverride = [
            "seekable"  => false
        ];

        // Test the method for the general use cases.
        $this->assertFalse($stream->isSeekable());
        $stream->streamGetMetaDataOverride["seekable"] = true;
        $this->assertTrue($stream->isSeekable());

        // The method should not throw even if the resource is not valid.
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeek(): void
    {
        // Test the method for the general use case.
        $stream = $this->createReadWriteStream();
        $stream->write("Hello World!");
        $this->assertNotEquals(0, $stream->tell());
        $stream->seek(0);
        $this->assertEquals(0, $stream->tell());
        $stream->seek(5);
        $this->assertEquals(5, $stream->tell());
    }

    public function testSeekThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->seek(0);
    }

    public function testSeekThrowsIfIsSeekableIsFalse(): void
    {
        $stream = $this->createReadWriteStream();
        $stream->streamGetMetaDataOverride = [
            "seekable"  => false
        ];

        // Test that the method throws if the underlying stream is not seekable.
        $this->expectException(\RuntimeException::class);
        $stream->seek(0);
    }

    public function testSeekThrowsIfFSeekFails(): void
    {
        // Test that the method throws if fseek() fails
        $this->expectExceptionMessage("Call to fseek() failed.");
        $stream = $this->createReadWriteStream();
        $stream->fseekOverride = -1;
        $stream->seek(0);
    }

    public function testRewind(): void
    {
        // Test the method for the general use case.
        $stream = $this->createReadWriteStream();
        $stream->write("Hello World!");
        $this->assertNotEquals(0, $stream->tell());
        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
    }

    public function testIsWritable(): void
    {
        // Test that the method works for read-only, write-only and read-write resource streams.
        $this->assertFalse($this->createReadOnlyStream()->isWritable());
        $this->assertTrue($this->createWriteOnlyStream()->isWritable());
        $this->assertTrue($this->createReadWriteStream()->isWritable());

        // The method should not throw even if the resource is not valid.
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->assertFalse($stream->isWritable());
    }

    public function testWrite(): void
    {
        // Test the method for the general use case.
        $stream = $this->createReadWriteStream();
        $stream->write("Hello World!");
        $this->assertEquals("Hello World!", $stream->getContents());
    }

    public function testWriteThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->write("Hello World!");
    }

    public function testWriteThrowsIfIsWritableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not writable.
        $this->expectException(\RuntimeException::class);
        $this->createReadOnlyStream()->write("Hello World!");
    }

    public function testWriteThrowsIfFwriteFails(): void
    {
        // Test that the method throws if fwrite() fails
        $this->expectExceptionMessage("Call to fwrite() failed.");
        $stream = $this->createReadWriteStream();
        $stream->fwriteOverride = false;
        $stream->write("Hello World!");
    }

    public function testIsReadable(): void
    {
        // Test that the method works for read-only, write-only and read-write resource streams.
        $this->assertTrue($this->createReadOnlyStream()->isReadable());
        $this->assertFalse($this->createWriteOnlyStream()->isReadable());
        $this->assertTrue($this->createReadWriteStream()->isReadable());

        // The method should not throw even if the resource is not valid.
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->assertFalse($stream->isReadable());
    }

    public function testRead(): void
    {
        // Test the method for the general use case.
        $stream = $this->createReadWriteStream();
        $stream->write("Hello World!");
        $stream->rewind();
        $this->assertEquals("Hello World!", $stream->read(12));
        $this->assertEquals("", $stream->read(12));
    }

    public function testReadThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->read(10);
    }

    public function testReadThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyStream()->read(10);
    }

    public function testReadThrowsIfFreadFails(): void
    {
        // Test that the method throws if fread() fails
        $this->expectExceptionMessage("Call to fread() failed.");
        $stream = $this->createReadWriteStream();
        $stream->freadOverride = false;
        $stream->read(10);
    }

    public function testGetContents(): void
    {
        // Test the method for the general use case.
        // (No seek necessary and multiple calls work unlike read).
        $stream = $this->createReadWriteStream();
        $stream->write("Hello World!");
        $this->assertEquals("Hello World!", $stream->getContents());
        $this->assertEquals("Hello World!", $stream->getContents());
    }

    public function testGetContentsThrowsIfUnderlyingResourceIsNotValid(): void
    {
        // Test that the method will throw if the resource is not valid (ex. has been closed).
        $stream = $this->createReadWriteStream();
        $stream->close();
        $this->expectExceptionMessage(self::ASSERT_INVALID_MESSAGE);
        $stream->getContents();
    }

    public function testGetContentsThrowsIfIsReadableIsFalse(): void
    {
        // Test that the method throws if the underlying stream is not readable.
        $this->expectException(\RuntimeException::class);
        $this->createWriteOnlyStream()->getContents();
    }

    public function testGetContentsThrowsIfStreamGetContentsFails(): void
    {
        // Test that the method throws if streamGetContents() fails
        $this->expectExceptionMessage("Call to streamGetContents() failed.");
        $stream = $this->createReadWriteStream();
        $stream->streamGetContentsOverride = false;
        $stream->getContents();
    }

    public function testGetMetadata(): void
    {
        // Test the method for the general use case.
        $stream = $this->createReadWriteStream();
        $this->assertIsArray($stream->getMetadata());
        $this->assertEquals("w+b", $stream->getMetadata()["mode"]);
        $this->assertEquals("w+b", $stream->getMetadata("mode"));
        $this->assertNull($stream->getMetadata("dummy"));

        // The method should not throw even if the resource is not valid.
        $stream->close();
        $this->assertNull($stream->getMetadata("mode"));
        $this->assertEmpty($stream->getMetadata());
    }

    private function createReadOnlyStream(): TestableStream
    {
        $stream = $this->createReadWriteStream();
        $stream->streamGetMetaDataOverride = [
            "mode"      => "r",
            "seekable"  => true
        ];

        return $stream;
    }

    private function createWriteOnlyStream(): TestableStream
    {
        $stream = $this->createReadWriteStream();
        $stream->streamGetMetaDataOverride = [
            "mode"      => "w",
            "seekable"  => true
        ];

        return $stream;
    }

    private function createReadWriteStream(): TestableStream
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        return new TestableStream($resource);
    }
}
