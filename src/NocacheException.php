<?php

namespace NocacheDev\Laravel;

use RuntimeException;

class NocacheException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
