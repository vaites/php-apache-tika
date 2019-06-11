<?php

namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for command line mode
 */
class CLITest extends BaseTest
{
    /**
     * Create shared instances of clients
     */
    public static function setUpBeforeClass()
    {
        self::$client = Client::make(self::getPathForVersion(self::$version));
    }

    /**
     * Set path test
     */
    public function testSetPath()
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path);

        $this->assertEquals($path, $client->getPath());
    }

    /**
     * Set Java test
     */
    public function testSetBinary()
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path, '/usr/bin/java');

        $this->assertEquals('/usr/bin/java', $client->getJava());
    }

    /**
     * Test delayed check
     */
    public function testDelayedCheck()
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::prepare('/nonexistent/path/to/apache-tika.jar');
        $client->setPath($path);

        $this->assertStringEndsWith(self::$version, $client->getVersion());
    }

    /**
     * Get the full path of Tika app for a specified version
     *
     * @param   string  $version
     * @return  string
     */
    private static function getPathForVersion($version)
    {
        return self::$binaries . "/tika-app-{$version}.jar";
    }
}