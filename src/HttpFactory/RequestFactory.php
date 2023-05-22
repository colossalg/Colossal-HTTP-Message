<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\Http\Request;
use Psr\Http\Message\{ RequestFactoryInterface, RequestInterface, UriInterface };

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @see RequestFactoryInterface::createRequest()
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (!is_string($uri) && !($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException("Argument 'uri' must have type string or UriInterface.");
        }

        if (is_string($uri)) {
            $uri = (new UriFactory())->createUri($uri);
        }

        return (new Request())
            ->withMethod($method)
            ->withUri($uri);
    }
}
