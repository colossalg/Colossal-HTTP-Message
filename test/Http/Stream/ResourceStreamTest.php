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
    // public function testToString(): void
    // {
    //     // TODO
    // }

    // public function testClose(): void
    // {
    //     // TODO`
    // }

    // public function testDetach(): void
    // {
    //     // TODO
    // }

    // public function testGetSize(): void
    // {
    //     // TODO
    // }

    // public function testGetSizeThrowsIfUnderlyingResourceIsNotValid(): void
    // {
    //     // TODO
    // }

    // public function testTell(): void
    // {
    //     // TODO
    // }

    // public function testTellThrowsIfUnderlyingResourceIsNotValid(): void
    // {
    //     // TODO
    // }

    // public function testTellThrowsIfUnderlyingResourceIsAppendOnly(): void
    // {
    //     // TODO
    // }

    // public function testEof(): void
    // {
    //     // TODO
    // }

    // public function testEofThrowsIfUnderlyingResourceIsNotValid(): void
    // {
    //     // TODO
    // }

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
        return ResourceStream::createWithProvidedResource($resource);
    }

    private function createWriteOnlyResourceStream(): ResourceStream
    {
        // php://temp is read-write so we use stdout here in the tests for our write-only stream.
        $resource = fopen("php://stdout", "w");
        if ($resource === false) {
            throw new \RuntimeException("Failed to open php://stdout.");
        }
        return ResourceStream::createWithProvidedResource($resource);
    }

    private function createReadWriteResourceStream(): ResourceStream
    {
        $resource = fopen("php://temp", "r+b");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        return ResourceStream::createWithProvidedResource($resource);
    }
}
