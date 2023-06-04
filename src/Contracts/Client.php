<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Contracts;

interface Client
{
    public function getHTML(string $file, callable $callback = null, bool $append = true): ?string;
    public function getXHTML(string $file, callable $callback = null, bool $append = true): ?string;
    public function getText(string $file, callable $callback = null, bool $append = true): ?string;
    public function getMainText(string $file, callable $callback = null, bool $append = true): ?string;
    public function getMIME(string $file): ?string;
    public function getMetadata(string $file, bool $content = false): Metadata;
}