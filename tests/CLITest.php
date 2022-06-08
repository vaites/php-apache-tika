<?php declare(strict_types=1);

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
    public static function setUpBeforeClass(): void
    {
        self::$client = Client::make(self::getPathForVersion(self::$version));
    }

    /**
     * @testdox Document XHTML content is extracted
     *
     * @dataProvider encodingProvider
     */
    public function testDocumentXHTML(string $file): void
    {
        $this->assertStringStartsWith('<?xml version="1.0"', self::$client->getXHTML($file));
    }

    /**
     * @testdox Tika path can be set
     */
    public function testSetPath(): void
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path);

        $this->assertEquals($path, $client->getPath());
    }

    /**
     * @testdox Java binary can be set
     */
    public function testSetBinary(): void
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path, 'java');

        $this->assertEquals('java', $client->getJava());
    }

    /**
     * @testdox Java arguments can be set
     */
    public function testSetArguments(): void
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path);
        $client->setJavaArgs('-JXmx4g');

        $this->assertEquals('-JXmx4g', $client->getJavaArgs());
    }

    /**
     * @testdox Environment variables can be set
     */
    public function testSetEnvVars(): void
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::make($path);
        $client->setEnvVars(['LANG' => 'UTF-8']);

        $this->assertArrayHasKey('LANG', $client->getEnvVars());
    }

    /**
     * @testdox Version check can be delayed
     */
    public function testDelayedCheck(): void
    {
        $path = self::getPathForVersion(self::$version);

        $client = Client::prepare('/nonexistent/path/to/apache-tika.jar');
        $client->setPath($path);

        $this->assertStringContainsString(self::$version, $client->getVersion());
    }
}