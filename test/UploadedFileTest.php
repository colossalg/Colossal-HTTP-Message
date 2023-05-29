<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Colossal\Http\Message\Testable\{
    TestableStream,
    TestableUploadedFile
};
use Colossal\Http\Message\Utilities\NotSet;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \Colossal\Http\Message\UploadedFile
 * @uses \Colossal\Http\Message\Stream
 * @uses \Colossal\Http\Message\Utilities\Rfc3986
 */
final class UploadedFileTest extends TestCase
{
    private const UPLOADED_FILE_ERROR_EXCEPTION_MESSAGE      = "The uploaded file failed with error 'UPLOAD_ERR_INI_SIZE'.";
    private const UPLOADED_FILE_HAS_MOVED_EXCEPTION_MESSAGE  = "The uploaded file has been previously moved.";

    public function testBasicGetters1(): void
    {
        // Test the basic getters (mostly to complete coverage)
        $uploadedFile = UploadedFile::createFromFile(
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

    public function testBasicGetters2(): void
    {
        // Test the basic getters (mostly to complete coverage)
        $uploadedFile = UploadedFile::createFromStream(
            new Stream(null),
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
        $uploadedFile = $this->createUploadedFile("filePath");
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
        $uploadedFile = $this->createUploadedFile("oldFilePath");
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
        $this->createUploadedFile("oldFilePath")->moveTo(1); /** @phpstan-ignore-line */
    }

    public function testMoveToThrowsIfTargetPathIsDir(): void
    {
        // Test that the method throws if the target path is a directory
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing directory.");
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForMoveOverExistingDirOrUnwritableFile($uploadedFile, true, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testMoveToThrowsIfTargetPathIsNonWritable(): void
    {
        // Test that the method throws if the target path is an unwritable file
        $this->expectExceptionMessage("Path 'newFilePath' coincides with existing unwritable file.");
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForMoveOverExistingDirOrUnwritableFile($uploadedFile, false, true);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testFileMoveToThrowsIfRenameFailsInNonSapiEnv(): void
    {
        // Test that the method throws if the call to rename() fails
        $this->expectExceptionMessage("Call to rename() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForFileMoveToInNonSapiEnv($uploadedFile, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testFileMoveToThrowsIfIsUploadedFileFailsInSapiEnv(): void
    {
        // Test that the method throws if the call to isUploadedFile() fails
        $this->expectExceptionMessage("Call to isUploadedFile() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForFileMoveToInSapiEnv($uploadedFile, false, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testFileMoveToThrowsIfMoveUploadedFileFailsInSapiEnv(): void
    {
        // Test that the method throws if the call to moveUploadedFile() fails
        $this->expectExceptionMessage("Call to moveUploadedFile() returned false for 'oldFilePath'.");
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForFileMoveToInSapiEnv($uploadedFile, true, false);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testFileMoveToClosesStream(): void
    {
        $resource = fopen("php://temp", "r");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        // Test that the underlying stream is closed if the uploaded file is moved
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForFileMoveToInSapiEnv($uploadedFile, true, true, $resource);
        $stream = $uploadedFile->getStream();
        $uploadedFile->moveTo("newFilePath");
        $this->assertNull($stream->detach());
    }

    public function testStreamMoveToThrowsForNonReadableStream(): void
    {
        $stream = $this->createStreamWithStreamGetMetaDataOverrides([
            "mode"      => "w",
            "seekable"  => true
        ]);

        // Test that the method throws if the underlying stream is not readable
        $this->expectExceptionMessage("Call to streamMoveTo() when the stream is not readable and seekable.");
        $uploadedFile = $this->createUploadedFile($stream);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testStreamMoveToThrowsForNonSeekableStream(): void
    {
        $stream = $this->createStreamWithStreamGetMetaDataOverrides([
            "mode"      => "r",
            "seekable"  => false
        ]);

        // Test that the method throws if the underlying stream is not seekable
        $this->expectExceptionMessage("Call to streamMoveTo() when the stream is not readable and seekable.");
        $uploadedFile = $this->createUploadedFile($stream);
        $uploadedFile->moveTo("newFilePath");
    }

    public function testStreamMoveToThrowsIfFopenFails(): void
    {
        $stream = $this->createStreamWithStreamGetMetaDataOverrides();

        $uploadedFile = $this->createUploadedFile($stream);
        $this->prepareOverridesForStreamMove($uploadedFile, false);

        // Test that the method throws if the call to fopen fails
        $this->expectExceptionMessage("Could not open file path 'newFilePath' for writing.");
        $uploadedFile->moveTo("newFilePath");
    }

    public function testStreamMoveToClosesStream(): void
    {
        $fopenOverride = fopen("php://temp", "r+");
        if ($fopenOverride === false || !is_resource($fopenOverride)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $stream = $this->createStreamWithStreamGetMetaDataOverrides();

        $uploadedFile = $this->createUploadedFile($stream);
        $this->prepareOverridesForStreamMove($uploadedFile, $fopenOverride);

        // Test that the underlying stream is closed if the uploaded file is moved
        $uploadedFile->moveTo("newFilePath");
        $this->assertNull($stream->detach());
    }

    private function createUploadedFile(
        string|StreamInterface $filePathOrStream,
        int $error = 0
    ): TestableUploadedFile {
        if (is_string($filePathOrStream)) {
            return TestableUploadedFile::createFromFile(
                $filePathOrStream,
                1000,
                $error,
                "clientFileName",
                "clientMediaType"
            );
        } else {
            return TestableUploadedFile::createFromStream(
                $filePathOrStream,
                null,
                $error,
                "clientFileName",
                "clientMediaType"
            );
        }
    }

    private function createUploadedFileThatHasMoved(): TestableUploadedFile
    {
        $uploadedFile = $this->createUploadedFile("oldFilePath");
        $this->prepareOverridesForFileMoveToInNonSapiEnv($uploadedFile, true);
        $uploadedFile->moveTo("newFilePath");

        return $uploadedFile;
    }

    private function createStreamWithStreamGetMetaDataOverrides(
        NotSet|array $streamGetMetaDataOverrides = new NotSet()
    ): StreamInterface {
        $resource = fopen("php://temp", "r+");
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Call to fopen() failed.");
        }

        $stream = new TestableStream($resource);
        $stream->streamGetMetaDataOverride = $streamGetMetaDataOverrides;

        return $stream;
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

    private function prepareOverridesForFileMoveToInNonSapiEnv(
        TestableUploadedFile $uploadedFile,
        bool $renameOverride
    ): void {
        $uploadedFile->isDirOverride = false;
        $uploadedFile->isFileOverride = false;
        $uploadedFile->phpSapiNameOverride = "cli";
        $uploadedFile->renameOverride = $renameOverride;
    }

    private function prepareOverridesForFileMoveToInSapiEnv(
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

    private function prepareOverridesForStreamMove(
        TestableUploadedFile $uploadedFile,
        mixed $fopenOverride
    ): void {
        $uploadedFile->fopenOverride = $fopenOverride;
    }
}
