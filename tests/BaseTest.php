<?php namespace Vaites\ApacheTika\Tests;

use PHPUnit_Framework_TestCase;

use Vaites\ApacheTika\Client;

/**
 * Common test functionality
 */
abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Shared instance
     *
     * @var \Vaites\ApacheTika\Client
     */
    protected static $client = null;

    /**
     * Create shared instance of client
     */
    public static function setUpBeforeClass()
    {
        self::$client = Client::make();
    }

    /**
     * Metadata test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     * @param   string  $class
     */
    public function testMetadata($file, $class = 'Metadata')
    {
        $this->assertInstanceOf
        (
            "\\Vaites\\ApacheTika\\Metadata\\$class"   ,
            self::$client->getMetadata($file)
        );
    }

    /**
     * File provider using "samples" folder
     *
     * @param   string  $sample
     * @return  array
     */
    protected function samples($sample)
    {
        $samples = [];

        foreach(glob(dirname(__DIR__) . "/samples/$sample.*") as $sample)
        {
            $samples[basename($sample)] = [$sample];
        }

        return $samples;
    }

    /**
     * Main ile provider
     *
     * @return  array
     */
    abstract public function fileProvider();
}