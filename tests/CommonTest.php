<?php namespace Vaites\ApacheTika\Tests;

use PHPUnit_Framework_TestCase;

use Vaites\ApacheTika\Client;

/**
 * Common test functionality
 */
class CommonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Current tika version
     *
     * @var string
     */
    protected static $version = null;

    /**
     * Binary path (jars)
     *
     * @var string
     */
    protected static $binaries = null;

    /**
     * Shared client instance
     *
     * @var \Vaites\ApacheTika\Client
     */
    protected static $client = null;

    /**
     * Get env variables
     *
     * @param null      $name
     * @param array     $data
     * @param string    $dataName
     * @throws \Exception
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        self::$version = getenv('APACHE_TIKA_VERSION');
        self::$binaries = getenv('APACHE_TIKA_BINARIES');
        self::$client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar', 'java');

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Set chunk size test
     */
    public function testSetChunkSize()
    {
        self::$client->setChunkSize(42);

        $this->assertEquals(42, self::$client->getChunkSize());
    }

    /**
     * Set callback (closure) test
     */
    public function testSetClosureCallback()
    {
        self::$client->setCallback(function($chunk)
        {
            return trim($chunk);
        });

        $this->assertInstanceOf('Closure', self::$client->getCallback());
    }

    /**
     * Set callback (callable) test
     */
    public function testSetCallableCallback()
    {
        self::$client->setCallback('trim');

        $this->assertInstanceOf('Closure', self::$client->getCallback()); // callable is converted to closure
    }

    /**
     * Get supported versions test
     */
    public function testGetSupportedVersions()
    {
        $this->assertTrue(in_array('1.10', Client::getSupportedVersions()));
    }

    /**
     * Is version supported vtest
     */
    public function testIsVersionSupported()
    {
        $this->assertTrue(Client::isVersionSupported('1.10'));
    }
}
