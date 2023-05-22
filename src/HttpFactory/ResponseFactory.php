<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\Http\Response;
use Psr\Http\Message\{ ResponseFactoryInterface, ResponseInterface };

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @see ResponseFactoryInterface::createResponse()
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ""): ResponseInterface
    {
        return (new Response())->withStatus($code, $reasonPhrase);
    }
}