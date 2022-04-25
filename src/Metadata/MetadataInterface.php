<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

interface MetadataInterface
{
    /**
     * Sets an attribute
     */
    public function setAttribute(string $key, string $value): MetadataInterface;
}
