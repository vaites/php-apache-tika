<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for web mode
 */
class WebTest extends BaseTest
{
    protected static $process = null;

    /**
     * Start Tika server and create shared instance of clients
     */
    public static function setUpBeforeClass()
    {
        self::$client = Client::make('localhost', 9998, [CURLOPT_TIMEOUT => 15]);
    }

    /**
     * cURL options test
     */
    public function testCurlOptions()
    {
        $client = Client::make('localhost', 9998, [CURLOPT_TIMEOUT => 5]);
        $options = $client->getOptions();

        $this->assertEquals(5, $options[CURLOPT_TIMEOUT]);
    }

    /**
     * Setters and getters test
     */
    public function testSettersGetters()
    {
        $client = Client::make('localhost', 9998);
        $client->setHost('127.0.0.1');
        $client->setPort(9997);
        $client->setOptions([CURLOPT_TIMEOUT => 10]);

        $this->assertEquals('127.0.0.1', $client->getHost());
        $this->assertEquals(9997, $client->getPort());
        $this->assertEquals(10, $client->getOptions()[CURLOPT_TIMEOUT]);
    }
}