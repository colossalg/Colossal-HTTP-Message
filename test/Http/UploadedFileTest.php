<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\UploadedFile;
use Colossal\NotSet;
use Colossal\PhpOverrides;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\UploadedFile
 * @uses \Colossal\Http\Stream\ResourceStream
 * @uses \Colossal\Utilities\Rfc3986
 */
final class UploadedFileTest extends TestCase
{
    public const UPLOADED_FILE_ERROR_EXCEPTION_MESSAGE = "The uploaded file failed with error 'UPLOAD_ERR_INI_SIZE'.";
    public const UPLOADED_FILE_HAS_MOVED_EXCEPTION_MESSAGE = "The uploaded file has been previously moved.";

    public function setUp(): void
    {
        PhpOverrides::reset();
    }

    public function createUploadedFile(string $filePath, int $error): UploadedFile
    {
        return new UploadedFile(
            $filePath,
            1000,
            $error,
            "clientFileName",
            "clientMediaType"
        );
    }

    public function createUploadedFileThatHasMoved(): UploadedFile
    {
        $this->preparePhpOverridesForMoveToInNonSapiEnv(true);

        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $uploadedFile->moveTo("newFilePath");

        return $uploadedFile;
    }

    public function testBasicGetters(): void
    {
        // Test the basic getters (mostly to complete coverage)
        $uploadedFile = new UploadedFile(
            "filePath",
            1000,
            1,
            "clientFileName",
            "clientMediaType"
        );
        $this->assertEquals(1000, $uploadedFile->getSize());
        $this->assertEquals(1, $uploadedFile->getError());
        $this->assertEquals("clientFileName", $uploadedFile->getClientFilename());
        $this->assertEquals("clientMediaType", $uploadedFile->getClientMediaType());
    }

    public function testGetStream(): void
    {
        $resource = fopen("php://temp", "r");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }
        $this->preparePhpOverridesForAttemptToOpenStream($resource);

        // Test that the method works in the general case (lazy initialization works, etc.)
        $uploadedFile = $this->createUploadedFile("filePath", 0);
        $this->assertEquals($resource, $uploadedFile->getStream()->detach());

        fclose($resource);
    }

    public function testGetStreamThrowsIfErrorNotOk(): void
    {
        // Test that the method throws if the error of the uploaded file is not ok
        $this->expectExceptionMessage(self::UPLOADED_FILE_ERROR_EXCEPTION_MESSAGE);
        $this->createUploadedFile("filePath", 1)->getStream();
    }

    public function testGetStreamThrowsIfHasMoved(): void
    {
        // Test that the method throws if the uploaded file has already been moved
        $this->expectExceptionMessage(self::UPLOADED_FILE_HAS_MOVED_EXCEPTION_MESSAGE);
        $this->createUploadedFileThatHasMoved()->getStream();
    }

    public function testGetStreamThrowsIfFopenFails(): void
    {
        $this->preparePhpOverridesForAttemptToOpenStream(false);

        // Test that the method fails if the call to fopen() fails
        $this->expectException(\RuntimeException::class);
        $this->createUploadedFile("oldFilePath", 0)->getStream();
    }

    public function testMoveToThrowsIfErrorNotOk(): void
    {
        // Test that the method throws if the error of the uploaded file is not ok
        $this->expectExceptionMessage(self::UPLOADED_FILE_ERROR_EXCEPTION_MESSAGE);
        $this->createUploadedFile("oldFilePath", 1)->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfHasMoved(): void
    {
        // Test that the method throws if the uploaded file has already been moved
        $this->expectExceptionMessage(self::UPLOADED_FILE_HAS_MOVED_EXCEPTION_MESSAGE);
        $this->createUploadedFileThatHasMoved()->moveTo("newFilePath");
    }

    public function testMoveToThrowsForNonStringTargetPathArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'targetPath'
        $this->expectException(\InvalidArgumentException::class);
        $this->createUploadedFile("oldFilePath", 0)->moveTo(1); /** @phpstan-ignore-line */
    }

    public function testMoveToThrowsIfTargetPathIsDir(): void
    {
        $this->preparePhpOverridesForMoveOverExistingDirOrUnwritableFile(
            is_dir: true,
            is_file: false,
        );

        // Test that the method throws if the target path is a directory
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing directory.");
        $this->createUploadedFile("oldFilePath", 0)->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfTargetPathIsNonWritable(): void
    {
        $this->preparePhpOverridesForMoveOverExistingDirOrUnwritableFile(
            is_dir: false,
            is_file: true,
        );

        // Test that the method throws if the target path is an unwritable file
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing unwritable file.");
        $this->createUploadedFile("oldFilePath", 0)->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfRenameFailsInNonSapiEnv(): void
    {
        $this->preparePhpOverridesForMoveToInNonSapiEnv(false);

        // Test that the method throws if the call to rename() fails
        $this->expectExceptionMessage("Call to rename() returned false for 'oldFilePath'.");
        $this->createUploadedFile("oldFilePath", 0)->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfIsUploadedFileFailsInSapiEnv(): void
    {
        $this->preparePhpOverridesForMoveToInSapiEnv(
            is_uploaded_file: false,
            move_uploaded_file: false
        );

        // Test that the method throws if the call to is_uploaded_file() fails
        $this->expectExceptionMessage("Call to is_uploaded_file() returned false for 'oldFilePath'.");
        $this->createUploadedFile("oldFilePath", 0)->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfMoveUploadedFileFailsInSapiEnv(): void
    {
        $this->preparePhpOverridesForMoveToInSapiEnv(
            is_uploaded_file: true,
            move_uploaded_file: false
        );

        // Test that the method throws if the call to move_uploaded_file() fails
        $this->expectExceptionMessage("Call to move_uploaded_file() returned false for 'oldFilePath'.");
        $this->createUploadedFile("oldFilePath", 0)->moveTo("newFilePath");
    }

    public function testStreamIsClosedWhenMoved(): void
    {
        $resource = fopen("php://temp", "r");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }
        $this->preparePhpOverridesForMoveToInSapiEnv(
            is_uploaded_file: true,
            move_uploaded_file: true,
            fopen: $resource
        );

        // Test that the underlying stream is closed if the uploaded file is moved
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $stream = $uploadedFile->getStream();
        $uploadedFile->moveTo("newFilePath");
        $this->assertNull($stream->detach());
    }

    private function preparePhpOverridesForAttemptToOpenStream(mixed $fopen): void
    {
        PhpOverrides::setInstance(new PhpOverrides(fopen: $fopen));
    }

    private function preparePhpOverridesForMoveOverExistingDirOrUnwritableFile(bool $is_dir, bool $is_file): void
    {
        PhpOverrides::setInstance(new PhpOverrides(
            is_dir: $is_dir,
            is_file: $is_file,
            is_writable: false
        ));
    }

    private function preparePhpOverridesForMoveToInNonSapiEnv(bool $rename): void
    {
        PhpOverrides::setInstance(new PhpOverrides(
            is_dir: false,
            is_file: false,
            php_sapi_name: "cli",
            rename: $rename
        ));
    }

    private function preparePhpOverridesForMoveToInSapiEnv(
        bool $is_uploaded_file,
        bool $move_uploaded_file,
        mixed $fopen = new NotSet()
    ): void {
        PhpOverrides::setInstance(new PhpOverrides(
            fopen: $fopen,
            is_dir: false,
            is_file: false,
            php_sapi_name: "apache",
            is_uploaded_file: $is_uploaded_file,
            move_uploaded_file: $move_uploaded_file
        ));
    }
}
