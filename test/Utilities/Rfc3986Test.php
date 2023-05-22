<?php

declare(strict_types=1);

namespace Colossal\Http\Message\Utilities;

use Colossal\Http\Message\Utilities\Rfc3986;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message\Utilities\Rfc3986
 */
final class Rfc3986Test extends TestCase
{
    public function testAreUriComponentsValid(): void
    {
        // The following test cases are formatted as follows:
        //      - [0] Whether the test should pass/fail.
        //      - [1] The key of the component to change.
        //      - [2] The val of the component to change.
        $testCases = [
            // Test each of the individual components on their own
            [true,  "scheme", "http"],
            [false, "scheme", "1234"],
            [true,  "user", "root"],
            [false, "user", "%GG"],
            [true,  "host", "localhost"],
            [false, "host", "%GG"],
            [true,  "port", 8080],
            [false, "port", -1],
            [true,  "path", "/users/1"],
            [false, "path", "%GG"],
            [true,  "query", "first_name=John&last_name=Doe"],
            [false, "query", "%GG"],
            [true,  "fragment", "index"],
            [false, "fragment", "%GG"]
        ];

        foreach ($testCases as $testCase) {
            $components = [
                "scheme"    => null,
                "user"      => null,
                "pass"      => null,
                "host"      => null,
                "port"      => null,
                "path"      => null,
                "query"     => null,
                "fragment"  => null
            ];

            $components[$testCase[1]] = $testCase[2];

            $this->assertEquals(
                $testCase[0],
                Rfc3986::areUriComponentsValid($components),
                "Failed for test case '$testCase[1]' = '$testCase[2]'."
            );
        }
    }

    public function testParseUriIntoComponents(): void
    {
        // The following test cases are formatted as follows:
        //     - Key    => The uri to be parsed in to its components.
        //     - Value  => The parsed components of the URI:
        //          - [0] => The scheme.
        //          - [1] => The authority.
        //          - [2] => The path.
        //          - [3] => The query.
        //          - [4] => The fragment.
        $testCases = [
            // Test each of the individual components on their own (scheme, authority, host, path, query, fragment)
            ""                                                                  => [null, null, null, null, null],
            "http:"                                                             => ["http", null, null, null, null],
            "http://user:pass@authority:8080"                                   => ["http", "user:pass@authority:8080", null, null, null],
            "http:path"                                                         => ["http", null, "path", null, null],
            "http:?query"                                                       => ["http", null, null, "query", null],
            "http:#fragment"                                                    => ["http", null, null, null, "fragment"],
            // Test some fairly generic looking web URLs
            "http://localhost:8080/"                                            => ["http", "localhost:8080", "/", null, null],
            "http://localhost:8080/users"                                       => ["http", "localhost:8080", "/users", null, null],
            "http://localhost:8080/users/"                                      => ["http", "localhost:8080", "/users/", null, null],
            "http://localhost:8080/users/1"                                     => ["http", "localhost:8080", "/users/1", null, null],
            "http://localhost:8080/users?first_name=John&last_name=Doe"         => ["http", "localhost:8080", "/users", "first_name=John&last_name=Doe", null],
            "http://localhost:8080/users?first_name=John&last_name=Doe#profile" => ["http", "localhost:8080", "/users", "first_name=John&last_name=Doe", "profile"],
            "http://localhost:8080/users#friends"                               => ["http", "localhost:8080", "/users", null, "friends"],
        ];

        foreach ($testCases as $uri => $expectedComponents) {
            try {
                $components = Rfc3986::parseUriIntoComponents($uri);
                $this->assertEquals($expectedComponents[0], $components["scheme"]);
                $this->assertEquals($expectedComponents[1], $components["authority"]);
                $this->assertEquals($expectedComponents[2], $components["path"]);
                $this->assertEquals($expectedComponents[3], $components["query"]);
                $this->assertEquals($expectedComponents[4], $components["fragment"]);
            } catch (\InvalidArgumentException $e) {
                $this->fail("Parsing failed for uri '$uri'. Error message: $e.");
            }
        }
    }

    public function testParseAuthorityIntoComponents(): void
    {
        // The following test cases are formatted as follows:
        //     - Key    => The authority to be parsed in to its components.
        //     - Value  => The parsed components of the authority:
        //          - [0] => The user.
        //          - [1] => The pass.
        //          - [2] => The host.
        //          - [3] => The port.
        $testCases = [
            "user@"                 => ["user", null, "", null],
            "user@host"             => ["user", null, "host", null],
            "user@host:8080"        => ["user", null, "host", 8080],
            "user:pass@"            => ["user", "pass", "", null],
            "user:pass@host"        => ["user", "pass", "host", null],
            "user:pass@host:8080"   => ["user", "pass", "host", 8080],
            "localhost"             => [null, null, "localhost", null],
            "localhost:8080"        => [null, null, "localhost", 8080],
            "[A:B:C::]"             => [null, null, "[A:B:C::]", null],
            "[A:B:C::]:8080"        => [null, null, "[A:B:C::]", 8080]
        ];

        foreach ($testCases as $authority => $expectedComponents) {
            try {
                $components = Rfc3986::parseAuthorityIntoComponents($authority);
                $this->assertEquals($expectedComponents[0], $components["user"]);
                $this->assertEquals($expectedComponents[1], $components["pass"]);
                $this->assertEquals($expectedComponents[2], $components["host"]);
                $this->assertEquals($expectedComponents[3], $components["port"]);
            } catch (\InvalidArgumentException $e) {
                $this->fail("Parsing failed for authority '$authority'. Error message: $e.");
            }
        }
    }

    public function testIsValidScheme(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            ""          => false,
            "http"      => true,
            "Http"      => false,
            "HTTP"      => false,
            // Valid characters in various combinations
            "a"         => true,
            "A"         => false,
            "z"         => true,
            "Z"         => false,
            "a0"        => true,
            "aA"        => false,
            "+"         => false,
            "-"         => false,
            "."         => false,
            "a+z+0+9+"  => true,
            "a-z-0-9-"  => true,
            "a.z.0.9."  => true,
            "a+z-0.9"   => true,
            // 'A' followed by an invalid character
            "a%"        => false,
            "a:"        => false,
            "a/"        => false,
            "a?"        => false,
            "a#"        => false
        ];

        foreach ($testCases as $scheme => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidScheme($scheme), "Failed for case '$scheme'.");
        }
    }

    public function testEncodeScheme(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            "http"  => "http",
            "Http"  => "http",
            "HTTP"  => "http"
        ];

        foreach ($testCases as $testCase => $expected) {
            $this->assertEquals($expected, Rfc3986::encodeScheme($testCase));
        }
    }

    public function testEncodeSchemeThrowsForInvalidArgumentScheme(): void
    {
        // Test that the method throws if we pass it a value of the incorrect format for the argument 'scheme'
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encodeScheme("1");
    }

    public function testIsValidUserInfo(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            ""                          => true,
            "user"                      => true,
            "user:"                     => true,
            "user:pass"                 => true,
            ":"                         => true,
            ":user"                     => true,
            ":user:pass"                => true,
            ":user:pas:1:2"             => true,
            // All of the valid characters together
            "%00azAZ09-._~!$&'()*+,;="  => true,
            // Invalid characters and percent encodings
            "?"                         => false,
            "#"                         => false,
            "%A"                        => false,
            "%G"                        => false,
            "%GA"                       => false,
            "%AG"                       => false
        ];

        foreach ($testCases as $userInfo => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidUserInfo($userInfo));
        }
    }

    public function testEncodeUserInfo(): void
    {
        // Test that the method correctly encodes each character from the unreserved set, sub delims and gen delims
        $this->assertEquals("aAzZ09!$&'()*+,;=:%2F%3F%23%5B%5D%40", Rfc3986::encodeUserInfo("aAzZ09!$&'()*+,;=:/?#[]@"));
    }

    public function testIsValidHost(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            ""                          => true,
            "[1:2:3:4:5:6:7:8]"         => true,
            "[v7.1:2:3:4]"              => true,
            "localhost"                 => true,
            // All of the valid characters together
            "%00azAZ09-._~!$&'()*+,;="  => true,
            // Invalid characters and percent encodings
            "?"                         => false,
            "#"                         => false,
            "%A"                        => false,
            "%G"                        => false,
            "%GA"                       => false,
            "%AG"                       => false
        ];

        foreach ($testCases as $host => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidHost($host), "Failed for case '$host'.");
        }
    }

    public function testEncodeHost(): void
    {
        // Test that the method correctly encodes each character from the unreserved set, sub delims and gen delims
        // (String should also be converted to lower case)
        $this->assertEquals("aazz09!$&'()*+,;=%3A%2F%3F%23%5B%5D%40", Rfc3986::encodeHost("aAzZ09!$&'()*+,;=:/?#[]@"));

        // Test that we do not perform encoding for IPv6 or IPVFuture addresses
        $this->assertEquals("[1:2:3:4:5:6:7:8]", Rfc3986::encodeHost("[1:2:3:4:5:6:7:8]"));
        $this->assertEquals("[v7.1:2:3:4]", Rfc3986::encodeHost("[v7.1:2:3:4]"));
    }

    public function testIsValidPort(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            8000    => true,
            "8080"  => true,
            "1.1"   => false,
            "abc"   => false
        ];

        foreach ($testCases as $port => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidPort($port), "Failed for case '$port'.");
        }
    }

    public function testIsValidPath(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            ""              => true,
            "users"         => true,
            "users/"        => true,
            "users/1"       => true,
            "users/1/"      => true,
            "users/%AA"     => true,
            // Arbitrary numbers of consecutive slashes are allowed in the paths
            "/users/1"      => true,
            "//users/1"     => true,
            "///users/1"    => true,
            "users//1"      => true,
            // All of the valid characters together
            "%00azAZ09-._~!$&'()*+,;=:@/"   => true,
            // Invalid characters and percent encodings
            "?"             => false,
            "#"             => false,
            "["             => false,
            "]"             => false,
            "%A"            => false,
            "%G"            => false,
            "%GA"           => false,
            "%AG"           => false
        ];

        foreach ($testCases as $path => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidPath($path), "Failed for case '$path'.");
        }
    }

    public function testIsValidAbsolutePath(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            ""              => false,
            "users/1"       => false,
            "/users/1"      => true,
            "//users/1"     => false,
            "///users/1"    => false
        ];

        foreach ($testCases as $path => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidAbsolutePath($path), "Failed for case '$path'.");
        }
    }

    public function testEncodePath(): void
    {
        // Test that the method correctly encodes each character from the unreserved set, sub delims and gen delims
        $this->assertEquals("aAzZ09!$&'()*+,;=:/%3F%23%5B%5D@", Rfc3986::encodePath("aAzZ09!$&'()*+,;=:/?#[]@"));
    }

    public function testIsValidQuery(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            "first_name=John&last_name=Doe" => true,
            // All of the valid characters together
            "%00azAZ09-._~!$&'()*+,;=:@/?"  => true,
            // Invalid characters and percent encodings
            "#"     => false,
            "["     => false,
            "]"     => false,
            "%A"    => false,
            "%G"    => false,
            "%GA"   => false,
            "%AG"   => false
        ];

        foreach ($testCases as $query => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidQuery($query), "Failed for case '$query'.");
        }
    }

    public function testEncodeQuery(): void
    {
        // Test that the method correctly encodes each character from the unreserved set, sub delims and gen delims
        $this->assertEquals("aAzZ09!$&'()*+,;=:/?%23%5B%5D@", Rfc3986::encodeQuery("aAzZ09!$&'()*+,;=:/?#[]@"));
    }

    public function testIsValidFragment(): void
    {
        // Test that the method works in some general cases
        $testCases = [
            "index"                         => true,
            // All of the valid characters together
            "%00azAZ09-._~!$&'()*+,;=:@/?"  => true,
            // Invalid characters and percent encodings
            "#"     => false,
            "["     => false,
            "]"     => false,
            "%A"    => false,
            "%G"    => false,
            "%GA"   => false,
            "%AG"   => false
        ];

        foreach ($testCases as $fragment => $isValid) {
            $this->assertEquals($isValid, Rfc3986::isValidFragment($fragment), "Failed for case '$fragment'.");
        }
    }

    public function testEncodeFragment(): void
    {
        // Test that the method correctly encodes each character from the unreserved set, sub delims and gen delims
        $this->assertEquals("aAzZ09!$&'()*+,;=:/?%23%5B%5D@", Rfc3986::encodeFragment("aAzZ09!$&'()*+,;=:/?#[]@"));
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
        foreach (array_merge(Rfc3986::GEN_DELIMS, Rfc3986::SUB_DELIMS) as $reservedChar) {
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

    public function testIsPercentEncodingValid(): void
    {
        // Test the case where the percent encoding is valid
        $this->assertTrue(Rfc3986::isPercentEncodingValid("%00-testing-%99-testing-%AA-testing-%FF"));

        // Test the case where the percent encoding is (% HEXDIG NON-HEXDIG)
        $this->assertFalse(Rfc3986::isPercentEncodingValid("testing-%AG-testing"));

        // Test the case where the percent encoding is (% NON-HEXDIG HEXDIG)
        $this->assertFalse(Rfc3986::isPercentEncodingValid("testing-%GA-testing"));

        // Test the case where the percent encoding is (% NON-HEXDIG NON-HEXDIG)
        $this->assertFalse(Rfc3986::isPercentEncodingValid("testing-%GG-testing"));

        // Test the case where the percent encoding is at the end of the string and cut short (% HEXDIG)
        $this->assertFalse(Rfc3986::isPercentEncodingValid("testing-%A"));

        // Test the case where the percent encoding is at the end of the string and cut short (% NON-HEXDIG)
        $this->assertFalse(Rfc3986::isPercentEncodingValid("testing-%G"));
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
