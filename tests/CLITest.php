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
        $client = Client::make(self::getPathForVersion('1.0'));

        $this->assertEquals(self::getPathForVersion('1.0'), $client->getPath());
    }

    /**
     * Set Java test
     */
    public function testSetPort()
    {
        $client = Client::make(self::getPathForVersion('1.0'), '/opt/jdk/bin/java');

        $this->assertEquals('/opt/jdk/bin/java', $client->getJava());
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