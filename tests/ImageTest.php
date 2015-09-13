<?php namespace Vaites\ApacheTika\Tests;

/**
 * Test for images
 */
class ImageTest extends BaseTest
{
    /**
     * Metadata test
     *
     * @dataProvider    fileProvider
     * @param   string  $file
     */
    public function testMetadata($file, $class = 'Metadata')
    {
        parent::testMetadata($file, 'ImageMetadata');
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

        $this->assertEquals(1600, $meta->width, basename($file));
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

        $this->assertEquals(900, $meta->height, basename($file));
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
        return $this->samples('sample2');
    }

    /**
     * File provider for OCR using "samples" folder
     *
     * @return array
     */
    public function ocrProvider()
    {
        return $this->samples('sample3');
    }
}