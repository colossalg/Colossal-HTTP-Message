<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Http\{ Request, Uri };
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Request
 * @uses \Colossal\Http\Message
 * @uses \Colossal\Http\Stream
 * @uses \Colossal\Http\Uri
 * @uses \Colossal\Utilities\Rfc3986
 * @uses \Colossal\Utilities\Rfc7230
 * @uses \Colossal\Utilities\Utilities
 */
final class RequestTest extends TestCase
{
    private Request $request;

    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testGetRequestTarget(): void
    {
        $uriWithPath            = (new Uri())->withPath("/users");
        $uriWithPathAndQuery    = (new Uri())->withPath("/users")->withQuery("id=1");

        // Test that if a request target has been set then that is returned
        $newUri = $this->request
            ->withUri($uriWithPath)
            ->withRequestTarget("http://localhost:8080/user/1");
        $this->assertEquals("http://localhost:8080/user/1", $newUri->getRequestTarget());

        // Test that if no request target has been set but there is a Uri the origin-form of the Uri is returned

        $newUri = $this->request->withUri($uriWithPath);
        $this->assertEquals("/users", $newUri->getRequestTarget());

        $newUri = $this->request->withUri($uriWithPathAndQuery);
        $this->assertEquals("/users?id=1", $newUri->getRequestTarget());

        // Test that if no request target and no Uri are available then "/" is returned
        $this->assertEquals("/", $this->request->getRequestTarget());
    }

    public function testWithRequestTarget(): void
    {
        // Test that the method works in the general case
        $newRequest = $this->request->withRequestTarget("http://localhost:8000/users?id=1");
        $this->assertEquals("/", $this->request->getRequestTarget());
        $this->assertEquals("http://localhost:8000/users?id=1", $newRequest->getRequestTarget());
    }

    public function testWithRequestTargetThrowsForNonStringRequestTargetArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'requestTarget'
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withRequestTarget(1);
    }

    public function testWithRequestTargetThrowsForUnrecognisedForm(): void
    {
        // Test that the method throws when the string argument 'requestTarget' is in unrecognised form
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withRequestTarget("[]");
    }

    public function testWithMethod(): void
    {
        // Test that the method works in the general case
        $newRequest = $this->request->withMethod("POST");
        $this->assertEquals("GET", $this->request->getMethod());
        $this->assertEquals("POST", $newRequest->getMethod());
    }

    public function testWithMethodThrowsForNonStringMethodArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'method'
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withMethod(1); /** @phpstan-ignore-line */
    }

    public function testWithMethodThrowsForUnsuportedHttpMethod(): void
    {
        // Test that the method throws when we provide it with an unsupported Http method
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withMethod("UNSUPPORTED_METHOD");
    }

    public function testWithURi(): void
    {
        $uriWithoutHost = new Uri();
        $uriWithHost    = (new Uri())->withHost("www.google.com");

        // Test when the Uri doesn't contain a host component, the host header is non-empty and preserve host is false
        $newRequest = $this->request
            ->withHeader("host", "localhost")
            ->withUri($uriWithoutHost);
        $this->assertEquals(["localhost"], $newRequest->getHeader("host"));

        // Test when the Uri does contain a host component, the host header is non-empty and preserve host is false
        $newRequest = $this->request
            ->withHeader("host", "localhost")
            ->withUri($uriWithHost);
        $this->assertEquals(["www.google.com"], $newRequest->getHeader("host"));

        // Test when the Uri doesn't contain a host component, the host header is empty and preserve host is true
        $newRequest = $this->request->withUri($uriWithoutHost, true);
        $this->assertEquals([], $newRequest->getHeader("host"));

        // Test when the Uri does contain a host component, the host header is empty and preserve host is true
        $newRequest = $this->request->withUri($uriWithHost, true);
        $this->assertEquals(["www.google.com"], $newRequest->getHeader("host"));

        // Test when the Uri does contain a host component, the host header is non-empty and preserve host is true
        $newRequest = $this->request
            ->withHeader("host", "localhost")
            ->withUri($uriWithHost, true);
        $this->assertEquals(["localhost"], $newRequest->getHeader("host"));
    }

    public function testWithUriThrowsForNonBoolPreserveHostArgument(): void
    {
        // Test that the method throws when we provide it with a non bool value for the argument 'preserveHost'
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withUri(new Uri(), 1); /** @phpstan-ignore-line */
    }
}
