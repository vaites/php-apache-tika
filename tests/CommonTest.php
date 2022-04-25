<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests;

use PHPUnit\Framework\TestCase;

use Vaites\ApacheTika\Client;

/**
 * Common test functionality
 */
class CommonTest extends TestCase
{
    /**
     * Current tika version
     */
    protected static string $version;

    /**
     * Binary path (jars)
     */
    protected static string $binaries;

    /**
     * Shared client instance
     */
    protected static Client $client;

    /**
     * Get env variables
     */
    public function __construct(string $name = null, array $data = array(), $dataName = '')
    {
        self::$version = getenv('APACHE_TIKA_VERSION');
        self::$binaries = getenv('APACHE_TIKA_BINARIES');
        self::$client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar', 'java');

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Set chunk size test
     */
    public function testSetChunkSize(): void
    {
        self::$client->setChunkSize(42);

        $this->assertEquals(42, self::$client->getChunkSize());
    }

    /**
     * Set download remote
     */
    public function testDownloadRemote(): void
    {
        self::$client->setDownloadRemote(true);

        $this->assertTrue(self::$client->getDownloadRemote());
    }

    /**
     * Set callback (closure) test
     */
    public function testSetClosureCallback(): void
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
    public function testSetCallableCallback(): void
    {
        self::$client->setCallback('trim');

        $this->assertInstanceOf('Closure', self::$client->getCallback()); // callable is converted to closure
    }

    /**
     * Get supported versions test
     */
    public function testGetSupportedVersions(): void
    {
        $this->assertTrue(in_array(self::$version, self::$client->getSupportedVersions()));
    }

    /**
     * Is version supported vtest
     */
    public function testIsVersionSupported(): void
    {
        $this->assertTrue(self::$client->isVersionSupported(self::$version));
    }
}
