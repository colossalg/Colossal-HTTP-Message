<?php

declare(strict_types=1);

namespace Colossal\Http\Message\Utilities;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message\Utilities\Utilities
 */
final class UtilitiesTest extends TestCase
{
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
            new \stdClass()
        ];

        foreach ($passingTestCases as $testCase) {
            $this->assertTrue(Utilities::isStringOrArrayOfStrings($testCase));
        }

        foreach ($failingTestCases as $testCase) {
            $this->assertFalse(Utilities::isStringOrArrayOfStrings($testCase));
        }
    }
}
