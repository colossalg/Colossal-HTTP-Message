<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message\Response
 * @uses \Colossal\Http\Message\Message
 * @uses \Colossal\Http\Message\Stream
 */
final class ResponseTest extends TestCase
{
    private Response $response;

    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function testWithStatus(): void
    {
        $defaultStatusCode  = Response::DEFAULT_STATUS_CODE;
        $reasonPhrases      = Response::VALID_STATUS_CODE_REASON_PHRASES;

        // Test that the method works in the general case
        $newResponse = $this->response->withStatus(100, "!");
        $this->assertEquals($defaultStatusCode, $this->response->getStatusCode());
        $this->assertEquals($reasonPhrases[$defaultStatusCode], $this->response->getReasonPhrase());
        $this->assertEquals(100, $newResponse->getStatusCode());
        $this->assertEquals("!", $newResponse->getReasonPhrase());

        // Test that the method assigns the RFC7231 default reason phrase if no reason phrase is provided
        $newResponse = $this->response->withStatus(404);
        $this->assertEquals(404, $newResponse->getStatusCode());
        $this->assertEquals($reasonPhrases[404], $newResponse->getReasonPhrase());
    }

    public function testWithStatusThrowsForNonIntStatusCodeArgument(): void
    {
        // Test that the method throws when we provide it with a non integer value for the argument 'statusCode'
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus("404"); /** @phpstan-ignore-line */
    }

    public function testWithStatusThrowsForNonStringResponseCodeArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'reasonPhrase'
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus(404, 404); /** @phpstan-ignore-line */
    }

    public function testWithStatusThrowsForInvalidStatusCode(): void
    {
        // Test that the method throws when we provide it with an invalid value for the argument 'statusCode'
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus(0);
    }
}
