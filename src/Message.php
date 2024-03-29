<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Colossal\Http\Message\Utilities\Utilities;
use Psr\Http\Message\{
    MessageInterface,
    StreamInterface
};

class Message implements MessageInterface
{
    public const DEFAULT_PROTOCOL_VERSION      = "1.1";
    public const SUPPORTED_PROTOCOL_VERSIONS   = ["1.0", "1.1"];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->protocolVersion  = self::DEFAULT_PROTOCOL_VERSION;
        $this->headers          = [];
        $this->body             = new Stream(null);
    }

    /**
     * @see MessageInterface::getProtocolVersion()
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @see MessageInterface::withProtocolVersion()
     */
    public function withProtocolVersion(string $version): static
    {
        if (!in_array($version, self::SUPPORTED_PROTOCOL_VERSIONS)) {
            throw new \InvalidArgumentException("The protocol version '$version' is not supported.");
        }

        $newMessage = clone $this;
        $newMessage->protocolVersion = $version;

        return $newMessage;
    }

    /**
     * @see MessageInterface::getHeaders()
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @see MessageInterface::hasHeader()
     */
    public function hasHeader(string $name): bool
    {
        foreach ($this->headers as $headerName => $_) {
            if (strcasecmp($name, $headerName) == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see MessageInterface::getHeader()
     */
    public function getHeader(string $name): array
    {
        foreach ($this->headers as $headerName => $headerValues) {
            if (strcasecmp($name, $headerName) == 0) {
                return $headerValues;
            }
        }

        return [];
    }

    /**
     * @see MessageInterface::getHeaderLine()
     */
    public function getHeaderLine(string $name): string
    {
        return implode(",", $this->getHeader($name));
    }

    /**
     * @see MessageInterface::withHeader()
     */
    public function withHeader(string $name, $value): static
    {
        if (!Utilities::isStringOrArrayOfStrings($value)) {
            throw new \InvalidArgumentException("Argument 'value' must have type string or string[].");
        }

        $valueAsArray = is_array($value) ? $value : [$value];

        $nameToSetValuesFor = $this->getMatchingHeaderNameIfExistsOrDefault($name);

        $newMessage = clone $this;
        $newMessage->headers[$nameToSetValuesFor] = $valueAsArray;

        return $newMessage;
    }

    /**
     * @see MessageInterface::withAddedHeader()
     */
    public function withAddedHeader(string $name, $value): static
    {
        if (!Utilities::isStringOrArrayOfStrings($value)) {
            throw new \InvalidArgumentException("Argument 'value' must have type string or string[].");
        }

        $valueAsArray = is_array($value) ? $value : [$value];

        $nameToSetValuesFor = $this->getMatchingHeaderNameIfExistsOrDefault($name);

        $newMessage = clone $this;
        $newMessage->headers[$nameToSetValuesFor] = array_merge(
            $this->getHeader($nameToSetValuesFor),
            $valueAsArray
        );

        return $newMessage;
    }

    /**
     * @see MessageInterface::withoutHeader()
     */
    public function withoutHeader(string $name): static
    {
        $nameToSplice = $this->getMatchingHeaderNameIfExistsOrDefault($name);

        $newMessage = clone $this;
        unset($newMessage->headers[$nameToSplice]);

        return $newMessage;
    }

    /**
     * @see MessageInterface::getBody()
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @see MessageInterface::withBody()
     */
    public function withBody(StreamInterface $body): static
    {
        $newMessage = clone $this;
        $newMessage->body = $body;

        return $newMessage;
    }

    /**
     * Performs a non case-sensitive search of all the current header names versus a name provided returning:
     *     - If a match is found    => The name of the matching header.
     *     - If no match is found   => The name provided.
     * @param string $name The name provided.
     * @return string Either the name of the matching header or the name provided.
     */
    private function getMatchingHeaderNameIfExistsOrDefault(string $name)
    {
        foreach ($this->headers as $headerName => $_) {
            if (strcasecmp($name, $headerName) == 0) {
                return $headerName;
            }
        }

        return $name;
    }

    /**
     * @var string The protocol version for the message.
     */
    private string $protocolVersion;

    /**
     * @var array<array<string>> The headers for the message.
     */
    private array $headers;

    /**
     * @var StreamInterface The body for the message.
     */
    private StreamInterface $body;
}
