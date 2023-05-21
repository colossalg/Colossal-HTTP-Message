<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\HttpFactory\Testable\TestableStreamFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\HttpFactory\StreamFactory
 * @uses \Colossal\Http\Stream
 */
class StreamFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->streamFactory = new TestableStreamFactory();
    }

    public function testCreateStream(): void
    {
        $str = "Hello World!";

        // Test that the method works in the general case
        $stream = $this->streamFactory->createStream($str);
        $this->assertEquals($str, $stream->getContents());
    }

    public function testCreateStreamFromFile(): void
    {
        $resource = fopen("php://temp", "r+");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $this->streamFactory->fopenOverride = $resource;

        // Test that the method works in the general case
        $stream = $this->streamFactory->createStreamFromFile("file", "r+");
        $this->assertEquals($resource, $stream->detach());
    }

    public function testCreateStreamFromFileThrowsIfModeIsInvalid(): void
    {
        $this->streamFactory->fopenOverride = false;

        // Test that the method throws if the mode is invalid
        $this->expectExceptionMessage("The mode 's+' is invalid.");
        $this->streamFactory->createStreamFromFile("file", "s+");
    }

    public function testCreateStreamFromFileThrowsIfFopenFails(): void
    {
        $this->streamFactory->fopenOverride = false;

        // Test that the method throws if the call to fopen fails
        $this->expectExceptionMessage("Could not open file path 'file' with mode 'r+'");
        $this->streamFactory->createStreamFromFile("file", "r+");
    }

    public function testCreateStreamFromResource(): void
    {
        $resource = fopen("php://temp", "r+");
        if ($resource === false) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        // Test that the method works in the general case
        $stream = $this->streamFactory->createStreamFromResource($resource);
        $this->assertEquals($resource, $stream->detach());
    }

    public function testCreateStreamFromResourceThrowsForNonResourceResourceArgument(): void
    {
        // Test that the method throws when we provide it with a non resource value for the argument 'resource'
        $this->expectException(\InvalidArgumentException::class);
        $this->streamFactory->createStreamFromResource(1); /** @phpstan-ignore-line */
    }

    private TestableStreamFactory $streamFactory;
}
