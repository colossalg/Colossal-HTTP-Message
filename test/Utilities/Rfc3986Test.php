<?php declare(strict_types=1);

use Colossal\Utilities\Rfc3986;
use PHPUnit\Framework\TestCase;

final class Rfc3986Test extends TestCase
{
    public function testRfc3986Encode(): void
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
                => "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile",
            // A HTTP URI that has already been encoded
            "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile"
                => "http%3A%2F%2Fdummy-website.com%2Fusers%2F1%3Ffirst_name%3DJohn%26last_name%3DDoe%23profile",
        ];

        foreach ($testCases as $testCase => $expected) {
            $this->assertEquals($expected, Rfc3986::encode($testCase));
        }
    }

    public function testRfc3986EncodeForIndividualReservedCharacters(): void
    {
        // Test that the method correcly encodes all the individual reserved characters
        foreach (array_merge(Rfc3986::GEN_DELIMS, Rfc3986::SUB_DELIMS) as $reservedChar)
        {
            $expected = "%" . strtoupper(bin2hex($reservedChar));
            $this->assertEquals($expected, Rfc3986::encode($reservedChar));
        }
    }

    public function testRfc3986EncodeForAllReservedCharactersAppended(): void
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

    public function testRfc3986EncodeThrowsIfStrArgumentIsNotAsciiEncoded(): void
    {
        // Test that the method will throw when the string contains non US-ASCII
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode(utf8_encode("\x5A\x6F\xEB"));
    }

    public function testRfc3986EncodeThrowsIfStrArgumentHasIllegalPercentEncoding1(): void
    {
        // Test the case where the percent encoding is (% HEXDIG NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode("testing-%AG-testing");
    }

    public function testRfc3986EncodeThrowsIfStrArgumentHasIllegalPercentEncoding2(): void
    {
        // Test the case where the percent encoding is (% NON-HEXDIG HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode("testing-%GA-testing");
    }

    public function testRfc3986EncodeThrowsIfStrArgumentHasIllegalPercentEncoding3(): void
    {
        // Test the case where the percent encoding is (% NON-HEXDIG NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode("testing-%GG-testing");
    }

    public function testRfc3986EncodeThrowsIfStrArgumentHasIllegalPercentEncoding4(): void
    {
        // Test the case where the percent encoding is at the end of the string and cut short (% HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode("testing-%A");
    }

    public function testRfc3986EncodeThrowsIfStrArgumentHasIllegalPercentEncoding5(): void
    {
        // Test the case where the percent encoding is at the end of the string and cut short (% NON-HEXDIG)
        $this->expectException(\InvalidArgumentException::class);
        Rfc3986::encode("testing-%G");
    }
}