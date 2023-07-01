<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Legacy;

use Vaites\ApacheTika\Clients\CLI as Client;

/**
 * Keep compatibility with old CLIClient class
 *
 * @method static CLI make(string $path, string $java = 'java', array $args = [])
 */
class CLI extends Client
{
    /**
     * Set the Java arguments
     */
    public function setJavaArgs(mixed $args): Client
    {
        return parent::setJavaArgs(is_string($args) ? [$args] : $args);
    }
}