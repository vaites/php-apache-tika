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

    /**
     * cURL options test
     */
    public function testCurlOptions()
    {
        self::$client = Client::make('localhost', 9998, [CURLOPT_TIMEOUT => 5]);

        $options = self::$client->getOptions();

        $this->assertEquals(5, $options[CURLOPT_TIMEOUT]);
    }
}