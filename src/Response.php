<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    public const DEFAULT_STATUS_CODE                = 200;
    public const VALID_STATUS_CODE_REASON_PHRASES   = [
        100 => "Continue",
        101 => "Switching Protocols",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Payload Too Large",
        414 => "URI Too Long",
        415 => "Unsuported Media Type",
        416 => "Range Not Satisfiable",
        417 => "Expectation Failed",
        426 => "Upgrade Required",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported"
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->statusCode   = self::DEFAULT_STATUS_CODE;
        $this->reasonPhrase = self::VALID_STATUS_CODE_REASON_PHRASES[self::DEFAULT_STATUS_CODE];
    }

    /**
     * Copy constructor.
     */
    public function __clone()
    {
        parent::__clone();
    }

    /**
     * @see ResponseInterface::getStatusCode()
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @see ResponseInterface::getReasonPhrase()
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @see ResponseInterface::withStatus()
     */
    public function withStatus($statusCode, $reasonPhrase = ""): static
    {
        if (!is_int($statusCode)) {
            throw new \InvalidArgumentException("Argument 'statusCode' must have type int.");
        }
        if (!is_string($reasonPhrase)) {
            throw new \InvalidArgumentException("Argument 'reasonPhrase' must have type string.");
        }

        if (!array_key_exists($statusCode, self::VALID_STATUS_CODE_REASON_PHRASES)) {
            throw new \InvalidArgumentException("Status code '$statusCode' is not valid.");
        }

        if ($reasonPhrase === "") {
            $reasonPhrase = self::VALID_STATUS_CODE_REASON_PHRASES[$statusCode];
        }

        $newResponse = clone $this;
        $newResponse->statusCode    = $statusCode;
        $newResponse->reasonPhrase  = $reasonPhrase;

        return $newResponse;
    }

    /**
     * @var int The status code for this response.
     */
    private int $statusCode;

    /**
     * @var string The reason phrase for this response.
     */
    private string $reasonPhrase;
}
