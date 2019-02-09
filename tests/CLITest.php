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
        $client = Client::make(self::getPathForVersion(self::$version));

        $this->assertEquals(self::getPathForVersion(self::$version), $client->getPath());
    }

    /**
     * Set Java test
     */
    public function testSetBinary()
    {
        $client = Client::make(self::getPathForVersion(self::$version), '/usr/bin/java');

        $this->assertEquals('/usr/bin/java', $client->getJava());
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