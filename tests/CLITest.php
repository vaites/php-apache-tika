<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for command line mode
 */
class CLITest extends BaseTest
{
    /**
     * Create shared instance of client
     */
    public static function setUpBeforeClass()
    {
        $jars = getenv('APACHE_TIKA_JARS');
        $version = getenv('APACHE_TIKA_VERSION');

        self::$client = Client::make("$jars/tika-app-$version.jar");
    }
}