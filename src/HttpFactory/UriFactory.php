<?php

declare(strict_types=1);

namespace Colossal\HttpFactory;

use Colossal\Http\Uri;
use Colossal\Utilities\Rfc3986;
use Psr\Http\Message\{ UriFactoryInterface, UriInterface };

class UriFactory implements UriFactoryInterface
{
    /**
     * @see UriFactoryInterface::createUri()
     */
    public function createUri(string $uri = ""): UriInterface
    {
        $components = Rfc3986::parseUriIntoComponents($uri);
        if (!Rfc3986::areUriComponentsValid($components)) {
            throw new \InvalidArgumentException(
                "The components parsed from the URI are not valid. " .
                "Please check that the URI is well formed as per RFC3986."
            );
        }

        $scheme     = $components["scheme"];
        $user       = $components["user"];
        $pass       = $components["pass"];
        $host       = $components["host"];
        $port       = $components["port"];
        $path       = $components["path"];
        $query      = $components["query"];
        $fragment   = $components["fragment"];

        $newUri = new Uri();
        $newUri = is_null($scheme)   ? $newUri : $newUri->withScheme($scheme);
        $newUri = is_null($user)     ? $newUri : $newUri->withUserInfo($user, $pass);
        $newUri = is_null($host)     ? $newUri : $newUri->withHost($host);
        $newUri = is_null($port)     ? $newUri : $newUri->withPort(intval($port));
        $newUri = is_null($path)     ? $newUri : $newUri->withPath($path);
        $newUri = is_null($query)    ? $newUri : $newUri->withQuery($query);
        $newUri = is_null($fragment) ? $newUri : $newUri->withFragment($fragment);

        return $newUri;
    }
}
