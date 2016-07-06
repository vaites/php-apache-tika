<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for web mode
 */
class WebTest extends BaseTest
{
    /**
     * Create shared instances of clients
     */
    public static function setUpBeforeClass()
    {
        foreach(self::$versions as $index=>$version)
        {
            self::$clients[$version] = Client::make('localhost', 9998 + $index);
        }
    }

    /**
     * cURL options test
     *
     * @dataProvider    versionProvider
     */
    public function testCurlOptions($version)
    {
        static $port = 9998;

        $client = Client::make('localhost', $port++, [CURLOPT_TIMEOUT => 5]);
        $options = $client->getOptions();

        $this->assertEquals(5, $options[CURLOPT_TIMEOUT]);
    }

    /**
     * Setters and getters test
     *
     * @dataProvider    versionProvider
     */
    public function testSettersGetters($version)
    {
        static $port = 9998;

        $client = Client::make('localhost', $port++);
        $client->setHost('127.0.0.1');
        $client->setPort(9997);
        $client->setOptions([CURLOPT_TIMEOUT => 10]);

        $this->assertEquals('127.0.0.1', $client->getHost());
        $this->assertEquals(9997, $client->getPort());
        $this->assertEquals(10, $client->getOptions()[CURLOPT_TIMEOUT]);
    }
}