<?php declare(strict_types=1);

use Colossal\Utilities\Utilities;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Utilities\Utilities
 */
final class UtilitiesTest extends TestCase
{
    public function testArrayClone(): void
    {
        // Test a simple case where we clone an array containing objects
        // and nested objects. === should be false as the objects do not
        // have the same identifiers.

        $nestedArray = [
            "a"             => "A",
            "b"             => "B",
            "nestedObject"  => new \stdClass
        ];

        $array = [
            1               => "1",
            2               => "2",
            "object"        => new \stdClass,
            "nestedArray"   => $nestedArray
        ];

        $this->assertFalse($array === Utilities::arrayClone($array));
    }

    public function testIsStringOrArrayOfStrings(): void
    {
        // Test that the method works for some general cases.
        
        $passingTestCases = [
            "string",
            [],
            ["string1", "string2", "string3"]
        ];
        $failingTestCases = [
            1,
            1.1,
            ["string1", ["string2", "string3"]],
            new \stdClass
        ];

        foreach ($passingTestCases as $testCase) {
            $this->assertTrue(Utilities::isStringOrArrayOfStrings($testCase));
        }

        foreach ($failingTestCases as $testCase) {
            $this->assertFalse(Utilities::isStringOrArrayOfStrings($testCase));
        }
    }
}