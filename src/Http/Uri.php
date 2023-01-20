<?php declare(strict_types=1);

namespace Colossal\Http;

use \Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    const SUPPORTED_SCHEMES_AND_DEFAULT_PORTS = [
        "http"  => 80,
        "https" => 443
    ];

    const TCP_LOWER_RANGE = 0;
    const TCP_UPPER_RANGE = 65535;

    private string      $scheme;
    private string      $user;
    private string      $password;
    private string      $host;
    private null|int    $port;
    private string      $path;
    private string      $query;
    private string      $fragment;

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
        $this->fragment = "";
    }

    /**
     * @see UriInterface::getScheme()
     */
    public function getScheme(): string
    {
        return strtolower($this->scheme);
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
        return strtolower($this->host);
    }

    /**
     * @see UriInterface::getPort()
     */
    public function getPort(): null|int
    {
        if ($this->scheme !== "") {
            if (is_null($this->port) || self::SUPPORTED_SCHEMES_AND_DEFAULT_PORTS[strtolower($this->scheme)] === $this->port) {
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
        // TODO -- encoding of path before returning
        return $this->path;
    }

    /**
     * @see UriInterface::getQuery()
     */
    public function getQuery(): string
    {
        // TODO -- encoding of query before returning
        return $this->query;
    }

    /**
     * @see UriInterface::getFragment()
     */
    public function getFragment()
    {
        // TODO -- encoding of fragment before returning
        return $this->fragment;
    }

    /**
     * @see UriInterface::withScheme()
     */
    public function withScheme($scheme): Uri
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
        $newUri->scheme = $scheme;

        return $newUri;
    }

    /**
     * @see UriInterface::withUserInfo()
     */
    public function withUserInfo($user, $password = null): Uri
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException("Argument 'user' must have type string.");
        }
        if (!is_null($password) && !is_string($password)) {
            throw new \InvalidArgumentException("Argument 'password' must have type null or string.");
        }

        $newUri = clone $this;
        if ($user !== "") {
            $newUri->user       = $user;
            $newUri->password   = !is_null($password) ? $password : "";
        } else {
            $newUri->user       = "";
            $newUri->password   = "";
        }

        return $newUri;
    }

    /**
     * @see UriInterface::withHost()
     */
    public function withHost($host): Uri
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException("Argument 'host' must have type string.");
        }

        $newUri = clone $this;
        $newUri->host = $host;

        return $newUri;
    }

    /**
     * @see UriInterface::withPort()
     */
    public function withPort($port): Uri
    {
        if (!is_null($port) && !is_int($port)) {
            throw new \InvalidArgumentException("Argument 'port' must have type null or int.");
        }
        if (!is_null($port)) {
            if ($port < self::TCP_LOWER_RANGE || self::TCP_UPPER_RANGE < $port) {
                throw new \InvalidArgumentException(
                    "Argument 'port' must be in range [" . self::TCP_LOWER_RANGE . ", " . self::TCP_UPPER_RANGE . "].");
            }
        }

        $newUri = clone $this;
        $newUri->port = $port;

        return $newUri;
    }

    /**
     * @see UriInterface::withPath()
     */
    public function withPath($path): Uri
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException("Argument 'path' must have type string.");
        }

        // TODO -- validate path before setting

        $newUri = clone $this;
        $newUri->path = $path;

        return $newUri;
    }

    /**
     * @see UriInterface::withQuery()
     */
    public function withQuery($query): Uri
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException("Argument 'query' must have type string.");
        }

        // TODO -- validate query before setting

        $newUri = clone $this;
        $newUri->query = $query;

        return $newUri;
    }

    /**
     * @see UriInterface::withFragment()
     */
    public function withFragment($fragment): Uri
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException("Argument 'fragment' must have type string.");
        }

        // TODO -- validate fragment before setting

        $newUri = clone $this;
        $newUri->fragment = $fragment;

        return $newUri;
    }

    /**
     * @see UriInterface::__toString()
     */
    public function __toString(): string
    {
        // TODO -- combine the various URI components in to URI and return

        return "";
    }
}