<?php declare(strict_types=1);

use Vaites\ApacheTika\Client;

{
/**
 * Keep compatibility with old class names
 */
    class_alias('Vaites\ApacheTika\Clients\CLI', 'Vaites\ApacheTika\Clients\CLIClient');
    class_alias('Vaites\ApacheTika\Clients\REST', 'Vaites\ApacheTika\Clients\WebClient');
    class_alias('Vaites\ApacheTika\Metadata', 'Vaites\ApacheTika\Clients\Metadata\Metadata');
    class_alias('Vaites\ApacheTika\Metadata\Document', 'Vaites\ApacheTika\Clients\Metadata\DocumentMetadata');
    class_alias('Vaites\ApacheTika\Metadata\Image', 'Vaites\ApacheTika\Clients\Metadata\ImageMetadata');
}

if(function_exists('tika') === false)
{
    /**
     * Get a class instance throwing an exception if check fails
     *
     * @param string|null     $param1   path or host
     * @param string|int|null $param2   Java binary path or port for web client
     * @param array           $options  options for cURL request
     * @param bool            $check    check JAR file or server connection
     * @return \Vaites\ApacheTika\Clients\CLI|\Vaites\ApacheTika\Clients\REST
     * @throws \Exception
     */
    function tika(string $param1 = null, $param2 = null, array $options = null, bool $check = true): Client
    {
        return Client::make($param1, $param2, $options, $check);
    }
}