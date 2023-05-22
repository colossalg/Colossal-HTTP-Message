<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Colossal\Http\Message\Utilities\Rfc7230;
use Psr\Http\Message\{ RequestInterface, UriInterface };

class Request extends Message implements RequestInterface
{
    public const DEFAULT_METHOD     = "GET";
    public const SUPPORTED_METHODS  = [
        "GET",
        "HEAD",
        "POST",
        "PUT",
        "PATCH",
        "DELETE",
        "CONNECT",
        "OPTIONS",
        "TRACE"
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->requestTarget    = "";
        $this->method           = self::DEFAULT_METHOD;
        $this->uri              = new Uri();
    }

    /**
     * Copy constructor.
     */
    public function __clone()
    {
        parent::__clone();

        $this->uri = clone $this->uri;
    }

    /**
     * @see RequestInterface::getRequestTarget()
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget === "") {
            $requestTarget = "/";
            if ($this->getUri()->getPath()  !== "") {
                $requestTarget = $this->getUri()->getPath();
            }
            if ($this->getUri()->getQuery() !== "") {
                $requestTarget .= "?" . $this->uri->getQuery();
            }

            return $requestTarget;
        } else {
            return $this->requestTarget;
        }
    }

    /**
     * @see RequestInterface::withRequestTarget()
     */
    public function withRequestTarget($requestTarget): static
    {
        if (!is_string($requestTarget)) {
            throw new \InvalidArgumentException("Argument 'requestTarget' must have type string.");
        }
        if (
            !Rfc7230::IsRequestTargetInOriginForm($requestTarget)       &&
            !Rfc7230::IsRequestTargetInAbsoluteForm($requestTarget)     &&
            !Rfc7230::IsRequestTargetInAuthorityForm($requestTarget)    &&
            !Rfc7230::IsRequestTargetInAsteriskForm($requestTarget)
        ) {
            throw new \InvalidArgumentException(
                "Argument 'requestTarget' is in an unrecognised form. " .
                "Must be in origin-form, absolute-form, authority-form or asterisk-form."
            );
        }

        $newRequest = clone $this;
        $newRequest->requestTarget = $requestTarget;

        return $newRequest;
    }

    /**
     * @see RequestInterface::getMethod()
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @see RequestInterface::withMethod()
     */
    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new \InvalidArgumentException("Argument 'method' must have type string.");
        }
        if (!in_array($method, self::SUPPORTED_METHODS)) {
            throw new \InvalidArgumentException("The HTTP method '$method' is not supported.");
        }

        $newRequest = clone $this;
        $newRequest->method = $method;

        return $newRequest;
    }

    /**
     * @see RequestInterface::getUri()
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @see RequestInterface::withUri()
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        if (!is_bool($preserveHost)) {
            throw new \InvalidArgumentException("Argument 'preserveHost' must have type bool.");
        }

        $newRequest = clone $this;
        $newRequest->uri = $uri;

        if ($uri->getHost() !== "" && !($preserveHost && $newRequest->getHeaderLine("host") !== "")) {
            $newRequest = $newRequest->withHeader("Host", $uri->getHost());
        }

        return $newRequest;
    }

    /**
     * @var string The target for this request.
     */
    private string $requestTarget;

    /**
     * @var string The method for this request.
     */
    private string $method;

    /**
     * @var UriInterface The URI for this request.
     */
    private UriInterface $uri;
}
