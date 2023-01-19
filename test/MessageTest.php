<?php declare(strict_types=1);

use Colossal\Http\Message;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    private Message $message;

    public function setUp(): void
    {
        $this->message = new Message;
    }

    public function testWithProtocolVersion(): void
    {
        // Test that the method works with the valid values as strings
        foreach (Message::SUPPORTED_PROTOCOL_VERSIONS as $version) {
            $newMessage = $this->message->withProtocolVersion($version);
            $this->assertEquals($version, $newMessage->getProtocolVersion());
            $this->assertEquals(Message::DEFAULT_PROTOCOL_VERSION, $this->message->getProtocolVersion());
        }

        // Test that the method works with the valid values as doubles
        foreach (Message::SUPPORTED_PROTOCOL_VERSIONS as $version) {
            $newMessage = $this->message->withProtocolVersion(floatval($version));
            $this->assertEquals($version, $newMessage->getProtocolVersion());
            $this->assertEquals(Message::DEFAULT_PROTOCOL_VERSION, $this->message->getProtocolVersion());
        }

        // Test that the method will throw an exception if we give it an invalid string value
        $this->expectException(\UnexpectedValueException::class);
        $this->message->withProtocolVersion("0.1");

        // Test that the method will throw an exception if we give it an invalid double value
        $this->expectException(\UnexpectedValueException::class);
        $this->message->withProtocolVersion(0.2);

        // Test that the method will throw an error if we give it an
        // argument with a type that can not be converted to a double
        $this->expectError();
        $this->message->withProtocolVersion([]);
    }
}