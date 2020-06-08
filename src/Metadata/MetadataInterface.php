<?php

namespace Vaites\ApacheTika\Metadata;

interface MetadataInterface
{
    /**
     * Sets an attribute
     *
     * @param   string  $key
     * @param   string  $value
     * @return  \Vaites\ApacheTika\Metadata\MetadataInterface
     */
    public function setAttribute(string $key, string $value): MetadataInterface;
}