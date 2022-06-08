<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Common test functionality
 */
class CommonTest extends TestCase
{
    /**
     * Shared client instance
     */
    protected static Client $client;

    /**
     * Get env variables
     */
    public function __construct(string $name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        self::$client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar', 'java');
    }

    /**
     * @testdox Chunk size can be set
     */
    public function testSetChunkSize(): void
    {
        self::$client->setChunkSize(42);

        $this->assertEquals(42, self::$client->getChunkSize());
    }

    /**
     * @testdox Remote download can be enabled
     */
    public function testDownloadRemote(): void
    {
        self::$client->setDownloadRemote(true);

        $this->assertTrue(self::$client->getDownloadRemote());
    }

    /**
     * @testdox Closure read callback can be set
     */
    public function testSetClosureCallback(): void
    {
        self::$client->setCallback(fn($chunk) => trim($chunk));

        $this->assertInstanceOf('Closure', self::$client->getCallback());
    }

    /**
     * @testdox Callable read callback can be set
     */
    public function testSetCallableCallback(): void
    {
        self::$client->setCallback('trim');

        $this->assertInstanceOf('Closure', self::$client->getCallback()); // callable is converted to closure
    }

    /**
     * @testdox Timezone can be set
     */
    public function testGetTimezone(): void
    {
        $timezone = 'Europe/Madrid';

        self::$client->setTimezone($timezone);

        $this->assertEquals($timezone, self::$client->getTimezone());
    }

    /**
     * @testdox Supported versions can be readed
     */
    public function testGetSupportedVersions(): void
    {
        $this->assertTrue(in_array(self::$version, self::$client->getSupportedVersions()));
    }

    /**
     * @testdox Current version is supported
     */
    public function testIsVersionSupported(): void
    {
        $this->assertTrue(self::$client->isVersionSupported(self::$version));
    }
}
