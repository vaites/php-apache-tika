<?php

use Vaites\ApacheTika\Client;

/**
 * Test for images
 */
class ImageTest extends PHPUnit_Framework_TestCase
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
     */
    public function testMetadata($file)
    {
        $this->assertInstanceOf
        (
            '\\Vaites\\ApacheTika\\Metadata\\ImageMetadata',
            self::$client->getMetadata($file)
        );
    }

    /**
     * Metadata width test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataWidth($file)
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals($meta->width, 1600, basename($file));
    }

    /**
     * Metadata height test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadataHeight($file)
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals($meta->height, 900, basename($file));
    }

    /**
     * OCR test
     *
     * @dataProvider    ocrProvider
     * @param   string  $file
     */
    public function testOCR($file)
    {
        $text = self::$client->getText($file);

        $this->assertRegExp('/voluptate/i', $text);
    }

    /**
     * File provider using "samples" folder
     *
     * @return array
     */
    public function fileProvider()
    {
        $samples = [];

        foreach(glob(dirname(__DIR__) . '/samples/sample2.*') as $sample)
        {
            $samples[basename($sample)] = [$sample];
        }

        return $samples;
    }

    /**
     * File provider for OCR using "samples" folder
     *
     * @return array
     */
    public function ocrProvider()
    {
        $samples = [];

        foreach(glob(dirname(__DIR__) . '/samples/sample3.*') as $sample)
        {
            $samples[basename($sample)] = [$sample];
        }

        return $samples;
    }
}