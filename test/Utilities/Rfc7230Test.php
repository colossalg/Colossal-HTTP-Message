<?php

declare(strict_types=1);

namespace Colossal\Http\Message\Utilities;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message\Utilities\Rfc7230
 * @uses \Colossal\Http\Message\Utilities\Rfc3986
 */
final class Rfc7230Test extends TestCase
{
    public function testIsRequestTargetInOriginForm(): void
    {
        // Test when the request target contains a valid absolute path and a valid query component
        $this->assertTrue(Rfc7230::isRequestTargetInOriginForm("/path?query=abc"));

        // Test when the request target contains a valid absolute path and no/empty query component
        $this->assertTrue(Rfc7230::isRequestTargetInOriginForm("/path"));
        $this->assertTrue(Rfc7230::isRequestTargetInOriginForm("/path?"));

        // Test when the request target contains an invalid absolute path and a valid query component
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("//path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("##path?query=abc"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm(""));

        // Test when the request target contains a valid absolute path and a valid query component
        // but the scheme, user, pass, host, port or fragment are present
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("http:path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("user:pass123@localhost:8080/path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("/path?query=abc#frag"));
    }

    public function testIsRequestTargetInAbsoluteForm(): void
    {
        // Test when the scheme, path and query components are correct and the fragment is absent
        $this->assertTrue(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000"));
        $this->assertTrue(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/"));
        $this->assertTrue(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users/1"));
        $this->assertTrue(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users?id=1"));

        // Test when the scheme, path and query components are correct and the fragment is present
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000#frag"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/#frag"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users/1#frag"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users?id=1#frag"));

        // Test when either the scheme, path or query components are incorrect
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("1234://localhost:8000/users?username=John_Doe"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/invalid[]path?id=1"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users?id=1[]"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm(""));
    }

    public function testIsRequestTargetInAuthorityForm(): void
    {
        // Test when the host and port components are present and correct
        $this->assertTrue(Rfc7230::isRequestTargetInAuthorityForm("localhost:8080"));

        // Test when the host is present and correct, and the port is absent
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("localhost"));

        // Test when the host is present and correct, and the port is present but incorrect
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("localhost:[]"));

        // Test when the host is present but incorrect, and the port is present and correct
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("[]:8080"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm(""));

        // Test when the host and port components are present and correct
        // but the user and pass components are present
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("root:password123@localhost:8080"));
    }

    public function testIsRequestTargetInAsteriskForm(): void
    {
        // Just for the sake of the code coverage metrics really
        $this->assertTrue(Rfc7230::isRequestTargetInAsteriskForm("*"));
    }
}
