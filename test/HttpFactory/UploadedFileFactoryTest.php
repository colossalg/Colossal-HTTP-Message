<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\Http\Stream;
use Colossal\HttpFactory\UploadedFileFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\HttpFactory\UploadedFileFactory
 * @uses \Colossal\Http\Stream
 * @uses \Colossal\Http\UploadedFile
 */
class UploadedFileFactoryTest extends TestCase
{
    public function testCreateUploadedFile(): void
    {
        $resource = fopen("php://temp", "r+");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $stream = new Stream($resource);

        $uploadedFile = (new UploadedFileFactory())->createUploadedFile(
            $stream,
            1000,
            0,
            "clientFilename",
            "clientMediaType"
        );

        $this->assertEquals($resource, $uploadedFile->getStream()->detach());
        $this->assertEquals(1000, $uploadedFile->getSize());
        $this->assertEquals(0, $uploadedFile->getError());
        $this->assertEquals("clientFilename", $uploadedFile->getClientFilename());
        $this->assertEquals("clientMediaType", $uploadedFile->getClientMediaType());
    }
}
