<?php

declare(strict_types=1);

namespace Colossal\Http;

use Colossal\Utilities\Rfc3986;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    public const SUPPORTED_SCHEMES_AND_DEFAULT_PORTS = [
        "http"  => 80,
        "https" => 443
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->scheme   = "";
        $this->user     = "";
        $this->password = "";
        $this->host     = "";
        $this->port     = null;
        $this->path     = "";
        $this->query    = "";
        $this->fragment = "";
    }

    /**
     * @see UriInterface::getScheme()
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @see UriInterface::getAuthority()
     */
    public function getAuthority(): string
    {
        $authority = $this->getHost();
        if ($authority !== "") {
            $userInfo   = $this->getUserInfo();
            $port       = $this->getPort();

            if ($userInfo !== "") {
                $authority = "$userInfo@$authority";
            }

            if (!is_null($port)) {
                $authority = "$authority:$port";
            }
        }

        return $authority;
    }

    /**
     * @see UriInterface::getUserInfo()
     */
    public function getUserInfo(): string
    {
        $userInfo = $this->user;
        if ($userInfo !== "" && $this->password !== "") {
            $userInfo = "$userInfo:$this->password";
        }

        return $userInfo;
    }

    /**
     * @see UriInterface::getHost()
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @see UriInterface::getPort()
     */
    public function getPort(): null|int
    {
        if ($this->scheme !== "") {
            $defaultPortForScheme = self::SUPPORTED_SCHEMES_AND_DEFAULT_PORTS[strtolower($this->scheme)];
            if (is_null($this->port) || $this->port === $defaultPortForScheme) {
                return null;
            }
        }

        return $this->port;
    }

    /**
     * @see UriInterface::getPath()
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @see UriInterface::getQuery()
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @see UriInterface::getFragment()
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @see UriInterface::withScheme()
     */
    public function withScheme($scheme): static
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException("Argument 'scheme' must have type string.");
        }
        $validScheme = false;
        foreach (self::SUPPORTED_SCHEMES_AND_DEFAULT_PORTS as $supportedScheme => $_) {
            if (strtolower($scheme) === $supportedScheme) {
                $validScheme = true;
            }
        }
        if (!$validScheme) {
            throw new \InvalidArgumentException("The scheme '$scheme' is not supported.");
        }

        $newUri = clone $this;
        $newUri->scheme = strtolower($scheme);

        return $newUri;
    }

    /**
     * @see UriInterface::withUserInfo()
     */
    public function withUserInfo($user, $password = null): static
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException("Argument 'user' must have type string.");
        }
        if (!is_null($password) && !is_string($password)) {
            throw new \InvalidArgumentException("Argument 'password' must have type null or string.");
        }

        $newUri = clone $this;
        if ($user !== "") {
            $newUri->user       = Rfc3986::encodeUserInfo($user);
            $newUri->password   = !is_null($password) ? Rfc3986::encodeUserInfo($password) : "";
        } else {
            $newUri->user       = "";
            $newUri->password   = "";
        }

        return $newUri;
    }

    /**
     * @see UriInterface::withHost()
     */
    public function withHost($host): static
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException("Argument 'host' must have type string.");
        }

        $newUri = clone $this;
        $newUri->host = Rfc3986::encodeHost($host);

        return $newUri;
    }

    /**
     * @see UriInterface::withPort()
     */
    public function withPort($port): static
    {
        if (!is_null($port) && !is_int($port)) {
            throw new \InvalidArgumentException("Argument 'port' must have type null or int.");
        }
        if (!is_null($port)) {
            if (!Rfc3986::isValidPort($port)) {
                throw new \InvalidArgumentException(
                    "Argument 'port' must be in range [" . Rfc3986::TCP_LOWER_PORT_RANGE . ", " . Rfc3986::TCP_UPPER_PORT_RANGE . "]."
                );
            }
        }

        $newUri = clone $this;
        $newUri->port = $port;

        return $newUri;
    }

    /**
     * @see UriInterface::withPath()
     */
    public function withPath($path): static
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException("Argument 'path' must have type string.");
        }

        $newUri = clone $this;
        $newUri->path = Rfc3986::encodePath($path);

        return $newUri;
    }

    /**
     * @see UriInterface::withQuery()
     */
    public function withQuery($query): static
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException("Argument 'query' must have type string.");
        }

        $newUri = clone $this;
        $newUri->query = Rfc3986::encodeQuery($query);

        return $newUri;
    }

    /**
     * @see UriInterface::withFragment()
     */
    public function withFragment($fragment): static
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException("Argument 'fragment' must have type string.");
        }

        $newUri = clone $this;
        $newUri->fragment = Rfc3986::encodeFragment($fragment);

        return $newUri;
    }

    /**
     * @see UriInterface::__toString()
     */
    public function __toString(): string
    {
        $scheme     = $this->getScheme();
        $authority  = $this->getAuthority();
        $path       = $this->getPath();
        $query      = $this->getQuery();
        $fragment   = $this->getFragment();

        $uri = "";

        if ($scheme !== "") {
            $uri = "$scheme:";
        }

        if ($authority !== "") {
            $uri = "$uri//$authority";
        }

        if ($authority !== "") {
            if ($path !== "" && !str_starts_with($path, "/")) {
                $path = "/$path";
            }
        } elseif (str_starts_with($path, "//")) {
            $rootlessPath = ltrim($path, "/");
            $path = "/$rootlessPath";
        }
        $uri = "$uri$path";

        if ($query !== "") {
            $uri = "$uri?$query";
        }

        if ($fragment !== "") {
            $uri = "$uri#$fragment";
        }

        return $uri;
    }

    /**
     * @var string The scheme component of the URI (encoded as per RFC 3986).
     */
    private string $scheme;

    /**
     * @var string The user, forming part of the user info component of the URI (encoded as per RFC 3986).
     */
    private string $user;

    /**
     * @var string The password, forming part of the user info component of the URI (encoded as per RFC 3986).
     */
    private string $password;

    /**
     * @var string The host, forming part of the authority component of the URI (encoded as per RFC3986).
     */
    private string $host;

    /**
     * @var null|int The port, forming part of the authority component of the URI (encoded as per RFC3986).
     */
    private null|int $port;

    /**
     * @var string The path component of the URI (encoded as per RFC3986).
     */
    private string $path;

    /**
     * @var string The query component of the URI (encoded as per RFC3986).
     */
    private string $query;

    /**
     * @var string The fragment component of the URI (encoded as per RFC3986).
     */
    private string $fragment;
}
