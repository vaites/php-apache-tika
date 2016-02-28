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
        self::$client = Client::make(getenv('APACHE_TIKA_JARS') . '/tika-app-1.12.jar');
    }
}