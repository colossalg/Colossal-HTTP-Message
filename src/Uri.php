<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Colossal\Http\Message\Utilities\Rfc3986;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    public const SUPPORTED_SCHEMES_AND_DEFAULT_PORTS = [
        "http"  => 80,
        "https" => 443
    ];

    /**
     * Create a new URI from the given string.
     * @param $uri The string to create the URI from.
     * @return self The URI that has been created.
     * @throws \InvalidArgumentException if $uri does not represent a well formed URI as per RFC3986.
     */
    public static function createUriFromString(string $uri): self
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

        $newUri = new self();
        $newUri = is_null($scheme)   ? $newUri : $newUri->withScheme($scheme);
        $newUri = is_null($user)     ? $newUri : $newUri->withUserInfo($user, $pass);
        $newUri = is_null($host)     ? $newUri : $newUri->withHost($host);
        $newUri = is_null($port)     ? $newUri : $newUri->withPort(intval($port));
        $newUri = is_null($path)     ? $newUri : $newUri->withPath($path);
        $newUri = is_null($query)    ? $newUri : $newUri->withQuery($query);
        $newUri = is_null($fragment) ? $newUri : $newUri->withFragment($fragment);

        return $newUri;
    }

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
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @see UriInterface::withScheme()
     */
    public function withScheme(string $scheme): static
    {
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
    public function withUserInfo(string $user, null|string $password = null): static
    {
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
    public function withHost(string $host): static
    {
        $newUri = clone $this;
        $newUri->host = Rfc3986::encodeHost($host);

        return $newUri;
    }

    /**
     * @see UriInterface::withPort()
     */
    public function withPort(null|int $port): static
    {
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
    public function withPath(string $path): static
    {
        $newUri = clone $this;
        $newUri->path = Rfc3986::encodePath($path);

        return $newUri;
    }

    /**
     * @see UriInterface::withQuery()
     */
    public function withQuery(string $query): static
    {
        $newUri = clone $this;
        $newUri->query = Rfc3986::encodeQuery($query);

        return $newUri;
    }

    /**
     * @see UriInterface::withFragment()
     */
    public function withFragment(string $fragment): static
    {
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
     * @var string The scheme component of the URI (encoded as per RFC3986).
     */
    private string $scheme;

    /**
     * @var string The user, forming part of the user info component of the URI (encoded as per RFC3986).
     */
    private string $user;

    /**
     * @var string The password, forming part of the user info component of the URI (encoded as per RFC3986).
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
