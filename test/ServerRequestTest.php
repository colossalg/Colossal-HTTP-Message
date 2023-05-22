<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Colossal\Http\Message\{ ServerRequest, UploadedFile };
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message\ServerRequest
 * @uses \Colossal\Http\Message\Message
 * @uses \Colossal\Http\Message\Request
 * @uses \Colossal\Http\Message\Stream
 * @uses \Colossal\Http\Message\UploadedFile
 * @uses \Colossal\Http\Message\Uri
 */
final class ServerRequestTest extends TestCase
{
    private ServerRequest $serverRequest;

    public function setUp(): void
    {
        $this->serverRequest = new ServerRequest();
    }

    public function testWithServerParams(): void
    {
        // Test that the method works in the general case.
        $serverParams = ['a' => 'A', 'b' => 'B'];
        $newServerRequest = $this->serverRequest->withServerParams($serverParams);
        $this->assertEquals([], $this->serverRequest->getServerParams());
        $this->assertEquals($serverParams, $newServerRequest->getServerParams());
    }

    public function testWithCookieParams(): void
    {
        // Test that the method works in the general case.
        $cookieParams = ['a' => 'A', 'b' => 'B'];
        $newServerRequest = $this->serverRequest->withCookieParams($cookieParams);
        $this->assertEquals([], $this->serverRequest->getCookieParams());
        $this->assertEquals($cookieParams, $newServerRequest->getCookieParams());
    }

    public function testWithQueryParams(): void
    {
        // Test that the method works in the general case.
        $queryParams = ['a' => 'A', 'b' => 'B'];
        $newServerRequest = $this->serverRequest->withQueryParams($queryParams);
        $this->assertEquals([], $this->serverRequest->getQueryParams());
        $this->assertEquals($queryParams, $newServerRequest->getQueryParams());
    }

    public function testWithUploadedFiles(): void
    {
        // Test that the method works in the general case.
        $uploadedFiles = [
            UploadedFile::createFromFile('path1', 100, 0, '', ''),
            UploadedFile::createFromFile('path2', 200, 0, '', '')
        ];
        $newServerRequest = $this->serverRequest->withUploadedFiles($uploadedFiles);
        $this->assertEquals([], $this->serverRequest->getUploadedFiles());
        $this->assertEquals($uploadedFiles, $newServerRequest->getUploadedFiles());
    }

    public function testWithUploadedFilesThrowsForNonUploadedFileArrayArgument(): void
    {
        // Test that the method throws when we provide it with an array
        // that does not fully consist of UploadedFileInterfaces for
        // the argument 'uploadedFiles'.
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withUploadedFiles([new \stdClass()]);
    }

    public function testWithParsedBody(): void
    {
        // Test that the method works in the general cases.

        $newServerRequest = $this->serverRequest->withParsedBody(null);
        $this->assertEquals(null, $this->serverRequest->getParsedBody());
        $this->assertEquals(null, $newServerRequest->getParsedBody());

        $parsedBodyArray = ['a' => 'A', 'b' => 'B'];
        $newServerRequest = $this->serverRequest->withParsedBody($parsedBodyArray);
        $this->assertEquals($parsedBodyArray, $newServerRequest->getParsedBody());

        $parsedBodyObject = new \stdClass();
        $parsedBodyObject->a = 'A';
        $parsedBodyObject->b = 'B';
        $newServerRequest = $this->serverRequest->withParsedBody($parsedBodyObject);
        $this->assertEquals($parsedBodyObject, $newServerRequest->getParsedBody());
    }

    public function testWithParsedBodyThrowsForNonNullOrArrayOrObjectArgument(): void
    {
        // Test that the method throws when we provide it with an
        // argument that is not null, an array, or an object for
        // the argument 'data'.
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withParsedBody(1); // @phpstan-ignore-line
    }

    public function testGetAttribute(): void
    {
        // Test that the method works in the general case.
        $newServerRequest = $this->serverRequest
            ->withAttribute('a', 'A')
            ->withAttribute('b', 'B');
        $this->assertEquals([], $this->serverRequest->getAttributes());
        $this->assertEquals('A', $newServerRequest->getAttribute('a'));
        $this->assertEquals('B', $newServerRequest->getAttribute('b'));
        $this->assertEquals('C', $newServerRequest->getAttribute('c', 'C'));
    }

    public function testGetAttributeThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'.
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->getAttribute(1, 'a'); // @phpstan-ignore-line
    }

    public function withAttribute(): void
    {
        // Test that the method works in the general case.
        $newServerRequest = $this->serverRequest
            ->withAttribute('a', 'A')
            ->withAttribute('b', 'B');
        $this->assertEquals([], $this->serverRequest->getAttributes());
        $this->assertEquals(['a' => 'A', 'b' => 'B'], $newServerRequest->getAttributes());
    }

    public function testWithAttributeThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'.
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withAttribute(1, 'a'); // @phpstan-ignore-line
    }

    public function testWithoutAttribute(): void
    {
        // Test that the method works in the general case.
        $this->serverRequest = $this->serverRequest
            ->withAttribute('a', 'A')
            ->withAttribute('b', 'B');
        $newServerRequest = $this->serverRequest->withoutAttribute('b')->withoutAttribute('c');
        $this->assertEquals(['a' => 'A', 'b' => 'B'], $this->serverRequest->getAttributes());
        $this->assertEquals(['a' => 'A'], $newServerRequest->getAttributes());
    }

    public function testWithoutAttributeThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'.
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withoutAttribute(1); // @phpstan-ignore-line
    }
}
