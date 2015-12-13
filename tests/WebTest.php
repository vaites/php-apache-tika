<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for web mode
 */
class WebTest extends BaseTest
{
    /**
     * Create shared instance of client
     */
    public static function setUpBeforeClass()
    {
        self::$client = Client::make('localhost');
    }
}