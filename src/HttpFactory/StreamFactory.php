<?php

namespace Colossal\HttpFactory;

use Colossal\Http\Stream;
use Psr\Http\Message\{ StreamInterface, StreamFactoryInterface };

class StreamFactory implements StreamFactoryInterface
{
    /**
     * @see StreamFactoryInterface::createStream()
     */
    public function createStream(string $content = ""): StreamInterface
    {
        $stream = self::createStreamFromFile("php://temp", "r+");
        $stream->write($content);

        return $stream;
    }

    /**
     * @see StreamFactoryInterface::createStreamFromFile()
     */
    public function createStreamFromFile(string $filename, string $mode = "r"): StreamInterface
    {
        $validModes = array_merge(
            Stream::READ_ONLY_MODES,
            Stream::WRITE_ONLY_MODES,
            Stream::READ_WRITE_MODES
        );
        if (!in_array($mode, $validModes, true)) {
            throw new \InvalidArgumentException("The mode '$mode' is invalid.");
        }

        $resource = $this->fopen($filename, $mode);
        if ($resource === false || !is_resource($resource)) {
            throw new \RuntimeException("Could not open file path '$filename' with mode '$mode'.");
        }

        return new Stream($resource);
    }

    /**
     * @see StreamFactoryInterface::createStreamFromResource()
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException("Argument 'resource' must have type resource.");
        }

        return new Stream($resource);
    }

    protected function fopen(string $filename, string $mode): mixed
    {
        return \fopen($filename, $mode);
    }
}
