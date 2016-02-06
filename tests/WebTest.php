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

    /**
     * Setters and getters test
     */
    public function testSettersGetters()
    {
        $client = Client::make('localhost', 9998);
        $client->setHost('127.0.0.1');
        $client->setPort(9999);
        $client->setOptions([CURLOPT_TIMEOUT => 10]);

        $this->assertEquals('127.0.0.1', $client->getHost());
        $this->assertEquals(9999, $client->getPort());
        $this->assertEquals(10, $client->getOptions()[CURLOPT_TIMEOUT]);
    }
}