<?php declare(strict_types=1);

namespace Colossal\Http;

use \Psr\Http\Message\MessageInterface;
use \Psr\Http\Message\StreamInterface;
use \UnexpectedValueException;

class Message implements MessageInterface
{
    const DEFAULT_PROTOCOL_VERSION      = "1.1";
    const SUPPORTED_PROTOCOL_VERSIONS   = ["1.0", "1.1"];

    private string $protocolVersion;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->protocolVersion = self::DEFAULT_PROTOCOL_VERSION;
    }

    /**
     * Copy constructor.
     */
    public function __clone()
    {
        // TODO
    }

    /**
     * @see MessageInterface::getProtocolVersion()
     */
    public function getProtocolVersion() : string
    {
        return $this->protocolVersion;
    }

    /**
     * @see MessageInterface::withProtocolVersion()
     */
    public function withProtocolVersion($version) : Message
    {
        $versionString = number_format(doubleval($version), 1);
        if (!in_array($versionString, self::SUPPORTED_PROTOCOL_VERSIONS)) {
            throw new \UnexpectedValueException("The protocol version $versionString is not a valid value.");
        }

        $newMessage = clone $this;
        $newMessage->protocolVersion = $versionString;

        return $newMessage;
    }

    /**
     * @see MessageInterface::getHeaders()
     */
    public function getHeaders()
    {
        // TODO
    }

    /**
     * @see MessageInterface::hasHeader()
     */
    public function hasHeader($name)
    {
        // TODO
    }

    /**
     * @see MessageInterface::getHeader()
     */
    public function getHeader($name)
    {
        // TODO
    }

    /**
     * @see MessageInterface::getHeaderLine()
     */
    public function getHeaderLine($name)
    {
        // TODO
    }

    /**
     * @see MessageInterface::withHeader()
     */
    public function withHeader($name, $value)
    {
        // TODO
    }

    /**
     * @see MessageInterface::withAddedHeader()
     */
    public function withAddedHeader($name, $value)
    {
        // TODO
    }

    /**
     * @see MessageInterface::withoutHeader()
     */
    public function withoutHeader($name)
    {
        // TODO
    }

    /**
     * @see MessageInterface::getBody()
     */
    public function getBody()
    {
        // TODO
    }

    /**
     * @see MessageInterface::withBody()
     */
    public function withBody(StreamInterface $body)
    {
        // TODO
    }
}