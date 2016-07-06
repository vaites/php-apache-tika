<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for command line mode
 */
class CLITest extends BaseTest
{
    /**
     * Create shared instances of clients
     */
    public static function setUpBeforeClass()
    {
        $jars = getenv('APACHE_TIKA_JARS');

        foreach(self::$versions as $version)
        {
            self::$clients[$version] = Client::make("$jars/tika-app-$version.jar");
        }
    }
}