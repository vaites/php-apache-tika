<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Contracts;

interface Metadata
{
    /**
     * Sets an attribute
     */
    public function setAttribute(string $key, string $value): Metadata;
}
