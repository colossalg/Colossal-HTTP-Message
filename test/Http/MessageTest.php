<?php

declare(strict_types=1);

namespace Colossal\Http\Testing;

use Colossal\Http\Message;
use Colossal\Http\Stream\NullStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Colossal\Http\Message
 * @uses \Colossal\Utilities\Utilities
 */
final class MessageTest extends TestCase
{
    private Message $message;

    public function setUp(): void
    {
        $this->message = new Message();
    }

    public function testWithProtocolVersion(): void
    {
        // Test that the method works with the valid values as strings
        foreach (Message::SUPPORTED_PROTOCOL_VERSIONS as $version) {
            $newMessage = $this->message->withProtocolVersion($version);
            $this->assertEquals(Message::DEFAULT_PROTOCOL_VERSION, $this->message->getProtocolVersion());
            $this->assertEquals($version, $newMessage->getProtocolVersion());
        }
    }

    public function testWithProtocolVersionThrowsForNonStringVersionArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'version'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withProtocolVersion(1); /** @phpstan-ignore-line */
    }

    public function testWithProtocolVersionThrowsWhenGivenNonSupportedVersion(): void
    {
        // Test that the method throws an exception when we try to set a non-supported protocol version
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withProtocolVersion("0.1");
    }

    public function testGetHeaders(): void
    {
        // Test that the method works in some general cases
        $newMessage = $this->message
            ->withHeader("header1", "value1")
            ->withHeader("header2", ["value2", "value3"]);
        $expected   = [
            "header1"   => ["value1"],
            "header2"   => ["value2", "value3"]
        ];
        $this->assertEquals($expected, $newMessage->getHeaders());
    }

    public function testHasHeader(): void
    {
        // Test that the method works in some general cases
        $newMessage = $this->message
            ->withHeader("header1", "value1")
            ->withHeader("header2", ["value2", "value3"]);
        $this->assertTrue($newMessage->hasHeader("header1"));
        $this->assertTrue($newMessage->hasHeader("header2"));
        $this->assertFalse($newMessage->hasHeader("header3"));
    }

    public function testHasHeaderThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->hasHeader(1); /** @phpstan-ignore-line */
    }

    public function testGetHeader(): void
    {
        // Test that the method works in some general cases
        $newMessage = $this->message
            ->withHeader("header1", "value1")
            ->withHeader("header2", ["value2", "value3"]);
        $this->assertEquals(["value1"], $newMessage->getHeader("header1"));
        $this->assertEquals(["value2", "value3"], $newMessage->getHeader("header2"));
        $this->assertEquals([], $newMessage->getHeader("header3"));
    }

    public function testGetHeaderThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->getHeader(1); /** @phpstan-ignore-line */
    }

    public function testWithHeader(): void
    {
        $headerName1    = "Name1";
        $headerValue1   = "A";

        $headerName2    = "Name2";
        $headerValue2   = ["B"];
        $headerValue3   = ["C", "D"];

        // Test that the method works when inserting a string value
        $newMessage = $this->message->withHeader($headerName1, $headerValue1);
        $this->assertFalse($this->message->hasHeader($headerName1));
        $this->assertEquals([$headerValue1], $newMessage->getHeader($headerName1));

        // Test that the method works when inserting an array value
        $newMessage = $this->message->withHeader($headerName2, $headerValue2);
        $this->assertFalse($this->message->hasHeader($headerName2));
        $this->assertEquals($headerValue2, $newMessage->getHeader($headerName2));

        // Test that the method overwrites existing headers for the new message but not the old message
        $newMessage2 = $newMessage->withHeader($headerName2, $headerValue3);
        $this->assertEquals($headerValue2, $newMessage->getHeader($headerName2));
        $this->assertEquals($headerValue3, $newMessage2->getHeader($headerName2));
    }

    public function testWithHeaderThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withHeader(1, "value"); /** @phpstan-ignore-line */
    }

    public function testWithHeaderThrowsForNonStringOrStringArrayValueArgument(): void
    {
        // Test that the method throws when we provide it with a non string or string[] value for the argument 'value'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withHeader("name", 1); /** @phpstan-ignore-line */
    }

    public function testWithAddedHeader(): void
    {
        $headerName1    = "Name1";
        $headerValue1   = "A";

        $headerName2    = "Name2";
        $headerValue2   = ["B"];
        $headerValue3   = ["C", "D"];

        // Test that the method works when inserting a string value for a header name that does not yet exist
        $newMessage = $this->message->withAddedHeader($headerName1, $headerValue1);
        $this->assertFalse($this->message->hasHeader($headerName1));
        $this->assertEquals([$headerValue1], $newMessage->getHeader($headerName1));

        // Test that the method works when inserting an array value for a header name that does not yet exist
        $newMessage = $this->message->withAddedHeader($headerName2, $headerValue2);
        $this->assertFalse($this->message->hasHeader($headerName2));
        $this->assertEquals($headerValue2, $newMessage->getHeader($headerName2));

        // Test that the method works when inserting a value for a header that already exists
        $newMessage2 = $newMessage->withAddedHeader($headerName2, $headerValue3);
        $this->assertEquals($headerValue2, $newMessage->getHeader($headerName2));
        foreach ($headerValue2 as $value) {
            $this->assertContains($value, $newMessage2->getHeader($headerName2));
        }
        foreach ($headerValue3 as $value) {
            $this->assertContains($value, $newMessage2->getHeader($headerName2));
        }
    }

    public function testWithAddedHeaderThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withAddedHeader(1, "value"); /** @phpstan-ignore-line */
    }

    public function testWithAddedHeaderThrowsForNonStringOrStringArrayValueArgument(): void
    {
        // Test that the method throws when we provide it with a non string or string[] value for the argument 'value'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withAddedHeader("name", 1); /** @phpstan-ignore-line */
    }

    public function testWithoutHeader(): void
    {
        // Test the general operation of the method
        $newMessage = $this->message->withHeader("header", "value");
        $newMessage2 = $newMessage->withoutHeader("header");
        $this->assertTrue($newMessage->hasHeader("header"));
        $this->assertFalse($newMessage2->hasHeader("header"));

        // Try removing a header that doesn't exist just to ensure we don't throw
        $this->message->withoutHeader("header");
    }

    public function testWithoutHeaderThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withoutHeader(1); /** @phpstan-ignore-line */
    }

    public function testGetHeaderLine(): void
    {
        // Test the general operation of the method
        $newMessage = $this->message->withHeader("header", ["A", "B", "C"]);
        $this->assertEquals("A,B,C", $newMessage->getHeaderLine("header"));

        // Test the edge case where we have no header that is an empty array
        $newMessage = $this->message->withHeader("header", []);
        $this->assertEquals("", $newMessage->getHeaderLine("header"));
    }

    public function testGetHeaderLineThrowsForNonStringNameArgument(): void
    {
        // Test that the method throws when we provide it with a non string value for the argument 'name'
        $this->expectException(\InvalidArgumentException::class);
        $this->message->getHeaderLine(1); /** @phpstan-ignore-line */
    }

    public function testWithBody(): void
    {
        // Test the general operation of the method
        $newMessage = $this->message->withBody(new NullStream());
        $this->assertFalse($this->message->getBody() === $newMessage->getBody());
    }
}
