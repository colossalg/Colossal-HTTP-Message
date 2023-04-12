<?php

declare(strict_types=1);

namespace Colossal\Utilities;

use Colossal\PhpOverrides;
use Colossal\Utilities\Rfc7230;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Utilities\Rfc7230
 * @uses \Colossal\Utilities\Rfc3986
 */
final class Rfc7230Test extends TestCase
{
    public function setUp(): void
    {
        PhpOverrides::reset();
        $this->phpOverrides = PhpOverrides::getInstance();
    }

    public function testIsRequestTargetInOriginForm(): void
    {
        // Test when the request target contains a valid absolute path and a valid query component
        $this->assertTrue(Rfc7230::isRequestTargetInOriginForm("/path?query=abc"));

        // Test when the request target contains a valid absolute path and no query component
        $this->assertTrue(Rfc7230::isRequestTargetInOriginForm("/path"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("/path?"));

        // Test when the request target contains a valid absolute path and an invalid query component
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("/path?query=abc#"));

        // Test when the request target contains an invalid absolute path and a valid query component
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("//path?query=abc"));
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm("##path?query=abc"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInOriginForm(""));
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
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("Http://localhost:8000/users?username=John_Doe"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/invalid[]path?id=1"));
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000/users?id=1[]"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm(""));

        // Test when Rfc3986::parseUriInToComponentsFails (preg_match() failing forces this to occur)
        $this->phpOverrides->preg_match = false;
        $this->assertFalse(Rfc7230::isRequestTargetInAbsoluteForm("http://localhost:8000"));
    }

    public function testIsRequestTargetInAuthorityForm(): void
    {
        // Test when the host and port components are present and correct
        $this->assertTrue(Rfc7230::isRequestTargetInAuthorityForm("localhost:8080"));

        // Test when the host is present and correct, and the port is absent
        $this->assertTrue(Rfc7230::isRequestTargetInAuthorityForm("localhost"));

        // Test when the host is present and correct, and the port is present but incorrect
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("localhost:[]"));

        // Test when the host is present but incorrect
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("[]"));
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm("[]:8080"));

        // Test when the request target is empty
        $this->assertFalse(Rfc7230::isRequestTargetInAuthorityForm(""));
    }

    public function testIsRequestTargetInAsteriskForm(): void
    {
        // Just for the sake of the code coverage metrics really
        $this->assertTrue(Rfc7230::isRequestTargetInAsteriskForm("*"));
    }

    private PhpOverrides $phpOverrides;
}
