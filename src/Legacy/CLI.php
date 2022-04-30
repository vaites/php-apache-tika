<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Legacy;

use Vaites\ApacheTika\Clients\CLI as Client;

/**
 * Keep compatibility with old CLICLient class
 */
class CLI extends Client
{
    public function __construct(string $path = null, string $java = null, bool $check = true)
    {
        parent::__construct($path, $java, null, $check);
    }
}