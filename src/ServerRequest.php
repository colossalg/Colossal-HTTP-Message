<?php

declare(strict_types=1);

namespace Colossal\Http\Message;

use Psr\Http\Message\{
    ServerRequestInterface,
    UploadedFileInterface
};

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->serverParams     = [];
        $this->cookieParams     = [];
        $this->queryParams      = [];
        $this->uploadedFiles    = [];
        $this->parsedBody       = null;
        $this->attributes       = [];
    }

    /**
     * @see ServerRequestInterface::getServerParams()
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Return an instance with the specified server params.
     *
     * This method is implemented in such a way as to retain the immutability of
     * the server request. It returns a new instance with the specified server
     * params.
     *
     * @param array $serverParams Array of key/value pairs representing server params.
     * @return static
     */
    public function withServerParams(array $serverParams): static
    {
        $newServerRequest = clone $this;
        $newServerRequest->serverParams = $serverParams;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::getCookieParams()
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @see ServerRequestInterface::withCookieParams()
     */
    public function withCookieParams(array $cookieParams): static
    {
        $newServerRequest = clone $this;
        $newServerRequest->cookieParams = $cookieParams;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::getQueryParams()
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @see ServerRequestInterface::withQueryParams()
     */
    public function withQueryParams(array $queryParams): static
    {
        $newServerRequest = clone $this;
        $newServerRequest->queryParams = $queryParams;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::getUploadedFiles()
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @see ServerRequestInterface::withUploadedFiles()
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (!($uploadedFile instanceof UploadedFileInterface)) {
                throw new \InvalidArgumentException("Argument 'uploadedFiles' must have type UploadedFileInterface[].");
            }
        }

        $newServerRequest = clone $this;
        $newServerRequest->uploadedFiles = $uploadedFiles;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::getParsedBody()
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    /**
     * @see ServerRequestInterface::withParsedBody()
     */
    public function withParsedBody($data): static
    {
        if (!is_null($data) && !is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException("Argument 'data' must have type null, array or object.");
        }

        $newServerRequest = clone $this;
        $newServerRequest->parsedBody = $data;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::getAttributes()
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @see ServerRequestInterface::getAttribute()
     */
    public function getAttribute(string $name, $default = null): mixed
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * @see ServerRequestInterface::withAttribute()
     */
    public function withAttribute(string $name, $value): static
    {
        $newServerRequest = clone $this;
        $newServerRequest->attributes[$name] = $value;

        return $newServerRequest;
    }

    /**
     * @see ServerRequestInterface::withoutAttribute()
     */
    public function withoutAttribute(string $name): static
    {
        $newServerRequest = clone $this;
        if (isset($newServerRequest->attributes[$name])) {
            unset($newServerRequest->attributes[$name]);
        }

        return $newServerRequest;
    }

    /**
     * @var array<mixed> The server params for this server request.
     */
    private array $serverParams;

    /**
     * @var array<mixed> The cookie params for this server request.
     */
    private array $cookieParams;

    /**
     * @var array<mixed> The query params for this server request.
     */
    private array $queryParams;

    /**
     * @var array<UploadedFileInterface> The uploaded files for this server request.
     */
    private array $uploadedFiles;

    /**
     * @var null|array<mixed>|object The parsed body for this server request.
     */
    private null|array|object $parsedBody;

    /**
     * @var array<mixed> The attributes for this server request.
     */
    private array $attributes;
}
