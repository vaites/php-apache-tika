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
        self::$client = Client::make('10.0.0.37', 9999);
        self::$client->setHost('localhost');
        self::$client->setPort(9998);
        self::$client->setOptions([CURLOPT_TIMEOUT => 10]);

        $this->assertEquals('localhost', self::$client->getHost());
        $this->assertEquals(9998, self::$client->getPort());
        $this->assertEquals(10, self::$client->getOptions()[CURLOPT_TIMEOUT]);
    }
}