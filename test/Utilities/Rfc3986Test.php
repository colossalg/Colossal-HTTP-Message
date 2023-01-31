<?php declare(strict_types=1);

use Colossal\Utilities\Rfc3986;
use PHPUnit\Framework\TestCase;

final class Rfc3986Test extends TestCase
{
    public function testEncodeSchemeThrowsForInvalidArgumentScheme(): void
    {
        // Test that the method throws if we pass it a value of the incorrect format for the argument 'scheme'
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encodeScheme("1");
    }

    public function testEncodeWithNoExclusions(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            // Spaces
            "A B C"                 => "A%20B%20C",
            // Path like strings
            "A/B/C"                 => "A%2FB%2FC",
            "A/B/C/"                => "A%2FB%2FC%2F",
            "/A/B/C"                => "%2FA%2FB%2FC",
            "/A/B/C/"               => "%2FA%2FB%2FC%2F",
            // A boolean expression
            "5 * (2 + 3) != 16"     => "5%20%2A%20%282%20%2B%203%29%20%21%3D%2016",
            // An email address
            "John.Doe@gmail.com"    => "John.Doe%40gmail.com",
            // A HTTP URI
            "http://dummy-website.com/users/1?first_name=John&last_name=Doe#profile"
                => "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile"
        ];

        foreach ($testCases as $testCase => $expected) {
            $this->assertEquals($expected, Rfc3986::encode($testCase));
        }
    }

    public function testEncodeWithExclusions(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            // Spaces
            "A B C"                 => [" "],
            // Path like strings
            "A/B/C"                 => ["/"],
            "A/B/C/"                => ["/"],
            "/A/B/C"                => ["/"],
            "/A/B/C/"               => ["/"],
            // A boolean expression
            "5 * (2 + 3) != 16"     => [" ", "*", "+", "=", "!", "(", ")"],
            // An email address
            "John.Doe@gmail.com"    => ["@"],
            // A HTTP URI
            "http://dummy-website.com/users/1?first_name=John&last_name=Doe#profile" => [":", "/", "?", "=", "&", "#"]
        ];

        foreach ($testCases as $testCase => $exclusions) {
            $this->assertEquals($testCase, Rfc3986::encode($testCase, $exclusions));
        }
    }

    public function testEncodeForIndividualReservedCharacters(): void
    {
        // Test that the method correcly encodes all the individual reserved characters
        foreach (array_merge(Rfc3986::GEN_DELIMS, Rfc3986::SUB_DELIMS) as $reservedChar)
        {
            $expected = "%" . strtoupper(bin2hex($reservedChar));
            $this->assertEquals($expected, Rfc3986::encode($reservedChar));
        }
    }

    public function testEncodeForAllReservedCharactersAppended(): void
    {
        // Test that the method correct encodes all the characters appended together

        $reservedSet = array_merge(Rfc3986::GEN_DELIMS, Rfc3986::SUB_DELIMS);

        $expected = implode(
            "",
            array_map(
                function (string $reservedChar) {
                    return "%" . strtoupper(bin2hex($reservedChar));
                },
                $reservedSet
            )
        );

        $this->assertEquals($expected, Rfc3986::encode(implode("", $reservedSet)));
    }

    public function testEncodeDoesNotDoubleEncodeExistingEncodings(): void
    {
        // Test that the method does not double encode any existing percent encodings
        $encodedHttpUrl = "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile";
        $this->assertEquals($encodedHttpUrl, Rfc3986::encode($encodedHttpUrl));
    }

    public function testEncodeSetsExistingEncodingsToUpperCase(): void
    {
        // Test that the method does not double encode any existing percent encodings
        $encodedHttpUrl = "http%3a%2f%2fdummy-website.com%2fusers%2f1%3ffirst_name%3dJohn%26last_name%3dDoe%23profile";
        $expected       = "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile";
        $this->assertEquals($expected, Rfc3986::encode($encodedHttpUrl));
    }

    public function testValidateIsAsciiPassingCase(): void
    {
        // Test that the method will not throw when the string contains only US-ASCII
        $this->expectNotToPerformAssertions();
        Rfc3986::validateIsAscii("abcABC123!@#");
    }

    public function testValidateIsAsciiFailureCase(): void
    {
        // Test that the method will throw when the string contains non US-ASCII
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validateIsAscii(utf8_encode("\x5A\x6F\xEB"));
    }

    public function testValidatePercentEncodingPassingCase(): void
    {
        // Test the case where the percent encoding is valid
        $this->expectNotToPerformAssertions();
        Rfc3986::validatePercentEncoding("%00-testing-%99-testing-%AA-testing-%FF");
    }

    public function testValidatePercentEncodingFailureCase1(): void
    {
        // Test the case where the percent encoding is (% HEXDIG NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validatePercentEncoding("testing-%AG-testing");
    }

    public function testValidatePercentEncodingFailureCase2(): void
    {
        // Test the case where the percent encoding is (% NON-HEXDIG HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validatePercentEncoding("testing-%GA-testing");
    }

    public function testValidatePercentEncodingFailureCase3(): void
    {
        // Test the case where the percent encoding is (% NON-HEXDIG NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validatePercentEncoding("testing-%GG-testing");
    }

    public function testValidatePercentEncodingFailureCase4(): void
    {
        // Test the case where the percent encoding is at the end of the string and cut short (% HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validatePercentEncoding("testing-%A");
    }

    public function testValidatePercentEncodingFailureCase5(): void
    {
        // Test the case where the percent encoding is at the end of the string and cut short (% NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::validatePercentEncoding("testing-%G");
    }

    public function testIsIPLiteral(): void
    {
        // Test some general cases, IPv6 addresses, IPvFuture addresses and garbage
        $testCases = [
            "[1:2:3:4:5:6:7:8]" => true,
            "1:2:3:4:5:6:7:8"   => false,
            "[v7.1:2:3:4]"      => true,
            "v7.1:2:3:4"        => false,
            "[www.google.com]"  => false,
            "www.google.com"    => false
        ];

        foreach ($testCases as $address => $expected) {
            $this->assertEquals($expected, Rfc3986::isIPLiteral($address), "Test failed for address $address");
        }
    }

    public function testIsIPv6Address(): void
    {
        // Test some general cases
        $testCases = [
            // Testing sub pattern      6( h16 ":" ) ls32
            "1:2:3:4:5:6:7:8:9"     => false,
            "1:2:3:4:5:6:7:8"       => true,
            "1:2:3:4:5:6:7"         => false,
            // Testing sub pattern      "::" 5( h16 ":" ) ls32
            "A::1:2:3:4:5:6:7"      => false,
            "::1:2:3:4:5:6:7:8"     => false,
            "::1:2:3:4:5:6:7"       => true,
            // Testing sub pattern      [ h16 ] "::" 4( h16 ":" ) ls32
            "A:B::1:2:3:4:5:6"      => false,
            "A::1:2:3:4:5:6"        => true,
            "::1:2:3:4:5:6"         => true,
            // Testing sub pattern      [ *1( h16 ":" ) h16 ] "::" 3( h16 ":" ) ls32
            "A:B:C::1:2:3:4:5"      => false,
            "A:B::1:2:3:4:5"        => true,
            "A::1:2:3:4:5"          => true,
            "::1:2:3:4:5"           => true,
            // Testing sub pattern      [ *2( h16 ":" ) h16 ] "::" 2( h16 ":" ) ls32
            "A:B:C:D::1:2:3:4"      => false,
            "A:B:C::1:2:3:4"        => true,
            "A::1:2:3:4"            => true,
            "::1:2:3:4"             => true,
            // Testing sub pattern      [ *3( h16 ":" ) h16 ] "::" 1( h16 ":" ) ls32
            "A:B:C:D:E::1:2:3"      => false,
            "A:B:C:D::1:2:3"        => true,
            "A::1:2:3"              => true,
            "::1:2:3"               => true,
            // Testing sub pattern      [ *4( h16 ":" ) h16 ] "::" ls32
            "A:B:C:D:E:F::1:2"      => false,
            "A:B:C:D:F::1:2"        => true,
            "A::1:2"                => true,
            "::1:2"                 => true,
            // Testing sub pattern      [ *5( h16 ":" ) h16 ] "::" h16
            "A:B:C:D:E:F:AA::1"     => false,
            "A:B:C:D:E:F::1"        => true,
            "A::1"                  => true,
            "::1"                   => true,
            // Testing sub pattern      [ *6( h16 ":" ) h16 ] "::"
            "A:B:C:D:E:F:AA:AB::"   => false,
            "A:B:C:D:E:F:AA::"      => true,
            "A::"                   => true,
            "::"                    => true,
            // Test some other stuff
            "G::"                   => false
        ];

        foreach ($testCases as $address => $expected) {
            $this->assertEquals($expected, Rfc3986::isIPv6Address($address), "Test failed for address $address");
        }
    }

    public function testIsIPvFutureAddress(): void
    {
        $validChars = array_merge(Rfc3986::UNRESERVED, Rfc3986::SUB_DELIMS, [":"]);

        // Test all of the valid chars individually
        foreach ($validChars as $validChar) {
            $address = "v0.$validChar";
            $this->assertTrue(Rfc3986::isIPvFutureAddress($address), "Test failed for address $address");
        }

        // Test all of the valid chars concatenated
        $validCharsConcatenated = implode($validChars);
        $address = "v0.$validCharsConcatenated";
        $this->assertTrue(Rfc3986::isIPvFutureAddress($address));

        // Test some other general cases
        $testCases = [
            "vg.A:B:C"              => false,
            "vG.A:B:C"              => false,
            "v6.1:2:3:4:5:6:7:8"    => true,
            "v6.::"                 => true
        ];

        foreach ($testCases as $address => $expected) {
            $this->assertEquals($expected, Rfc3986::isIPvFutureAddress($address), "Test failed for address $address");
        }
    }
}