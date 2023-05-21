<?php

declare(strict_types=1);

namespace Colossal\HttpFactory\Testable;

use Colossal\HttpFactory\StreamFactory;
use Colossal\Utilities\NotSet;

class TestableStreamFactory extends StreamFactory
{
    public function __construct(public mixed $fopenOverride = new NotSet())
    {
    }

    protected function fopen(string $filename, string $mode): mixed
    {
        if (!($this->fopenOverride instanceof NotSet)) {
            return $this->fopenOverride;
        } else {
            return parent::fopen($filename, $mode);
        }
    }
}
