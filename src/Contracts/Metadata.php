<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Contracts;

interface Metadata
{
    public function setAttribute(string $key, string $value): Metadata;
}
