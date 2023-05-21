<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\Http\UploadedFile;
use Psr\Http\Message\{ StreamInterface, UploadedFileInterface, UploadedFileFactoryInterface };

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * @see UploadedFileFactoryInterface::createUploadedFile()
     */
    public function createUploadedFile(
        StreamInterface $stream,
        null|int $size = null,
        int $error = \UPLOAD_ERR_OK,
        null|string $clientFilename = null,
        null|string $clientMediaType = null
    ): UploadedFileInterface {
        return UploadedFile::createFromStream(
            $stream,
            $size,
            $error,
            $clientFilename,
            $clientMediaType
        );
    }
}
