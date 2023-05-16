<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\HttpFactory\UriFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\HttpFactory\UriFactory
 * @uses \Colossal\Http\Uri
 * @uses \Colossal\Utilities\Rfc3986
 */
final class UriFactoryTest extends TestCase
{
    private UriFactory $uriFactory;

    public function setUp(): void
    {
        $this->uriFactory = new UriFactory();
    }

    public function testCreateUri(): void
    {
        $str = "http://root:password123@localhost:8080/users?first_name=John&last_name=Doe#profile";

        // This method is predominantly covered within the tests for Rfc3986.
        $uri = $this->uriFactory->createUri($str);
        $this->assertEquals($str, $uri->__toString());
    }

    public function testCreateUriThrowsForStringWhoseComponentsAreNotValid(): void
    {
        // Test that the method throws if the parsed components of the URI are not valid.
        $this->expectExceptionMessage(
            "The components parsed from the URI are not valid. " .
            "Please check that the URI is well formed as per RFC3986."
        );
        $this->uriFactory->createUri("%GG");
    }
}
