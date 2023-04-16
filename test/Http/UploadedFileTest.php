<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\UploadedFile;
use Colossal\Http\Testable\TestableUploadedFile;
use Colossal\Utilities\NotSet;
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

    public function createUploadedFile(string $filePath, int $error): TestableUploadedFile
    {
        return new TestableUploadedFile(
            $filePath,
            1000,
            $error,
            "clientFileName",
            "clientMediaType"
        );
    }

    public function createUploadedFileThatHasMoved(): TestableUploadedFile
    {
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveToInNonSapiEnv($uploadedFile, true);
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

        // Test that the method works in the general case (lazy initialization works, etc.)
        $uploadedFile = $this->createUploadedFile("filePath", 0);
        $this->prepareOverridesForAttemptToOpenStream($uploadedFile, $resource);
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
        // Test that the method fails if the call to fopen() fails
        $this->expectException(\RuntimeException::class);
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForAttemptToOpenStream($uploadedFile, false);
        $uploadedFile->getStream();
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
        // Test that the method throws if the target path is a directory
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing directory.");
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveOverExistingDirOrUnwritableFile($uploadedFile, true, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfTargetPathIsNonWritable(): void
    {
        // Test that the method throws if the target path is an unwritable file
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing unwritable file.");
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveOverExistingDirOrUnwritableFile($uploadedFile, false, true);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfRenameFailsInNonSapiEnv(): void
    {
        // Test that the method throws if the call to rename() fails
        $this->expectExceptionMessage("Call to rename() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveToInNonSapiEnv($uploadedFile, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfIsUploadedFileFailsInSapiEnv(): void
    {
        // Test that the method throws if the call to isUploadedFile() fails
        $this->expectExceptionMessage("Call to isUploadedFile() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveToInSapiEnv($uploadedFile, false, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfMoveUploadedFileFailsInSapiEnv(): void
    {
        // Test that the method throws if the call to moveUploadedFile() fails
        $this->expectExceptionMessage("Call to moveUploadedFile() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveToInSapiEnv($uploadedFile, true, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testStreamIsClosedWhenMoved(): void
    {
        $resource = fopen("php://temp", "r");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        // Test that the underlying stream is closed if the uploaded file is moved
        $uploadedFile = $this->createUploadedFile("oldFilePath", 0);
        $this->prepareOverridesForMoveToInSapiEnv($uploadedFile, true, true, $resource);
        $stream = $uploadedFile->getStream();
        $uploadedFile->moveTo("newFilePath");
        $this->assertNull($stream->detach());
    }

    private function prepareOverridesForAttemptToOpenStream(
        TestableUploadedFile $uploadedFile,
        mixed $fopenOverride
    ): void {
        $uploadedFile->fopenOverride = $fopenOverride;
    }

    private function prepareOverridesForMoveOverExistingDirOrUnwritableFile(
        TestableUploadedFile $uploadedFile,
        bool $isDirOverride,
        bool $isFileOverride
    ): void {
        $uploadedFile->isDirOverride = $isDirOverride;
        $uploadedFile->isFileOverride = $isFileOverride;
        $uploadedFile->isWritableOverride = false;
    }

    private function prepareOverridesForMoveToInNonSapiEnv(
        TestableUploadedFile $uploadedFile,
        bool $renameOverride
    ): void {
        $uploadedFile->isDirOverride = false;
        $uploadedFile->isFileOverride = false;
        $uploadedFile->phpSapiNameOverride = "cli";
        $uploadedFile->renameOverride = $renameOverride;
    }

    private function prepareOverridesForMoveToInSapiEnv(
        TestableUploadedFile $uploadedFile,
        bool $isUploadedFileOverride,
        bool $moveUploadedFileOverride,
        mixed $fopenOverride = new NotSet()
    ): void {
        $uploadedFile->fopenOverride = $fopenOverride;
        $uploadedFile->isDirOverride = false;
        $uploadedFile->isFileOverride = false;
        $uploadedFile->phpSapiNameOverride = "apache";
        $uploadedFile->isUploadedFileOverride = $isUploadedFileOverride;
        $uploadedFile->moveUploadedFileOverride = $moveUploadedFileOverride;
    }
}
