<?php declare(strict_types=1);

use Vaites\ApacheTika\Client;

if(function_exists('tika') === false)
{
    /**
     * Get a class instance throwing an exception if check fails
     *
     * @param string|null     $param1   path or host
     * @param string|int|null $param2   Java binary path or port for web client
     * @param array           $options  options for cURL request
     * @param bool            $check    check JAR file or server connection
     * @return \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
     * @throws \Exception
     */
    function tika(string $param1 = null, $param2 = null, array $options = null, bool $check = true): Client
    {
        return Client::make($param1, $param2, $options, $check);
    }
}