<?php namespace Vaites\ApacheTika\Tests;

use PHPUnit_Framework_TestCase;

use Vaites\ApacheTika\Client;

/**
 * Common test functionality
 */
abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Shared client instances
     *
     * @var \Vaites\ApacheTika\Client[]
     */
    protected static $clients = [];

    /**
     * Metadata test
     *
     * @dataProvider    fileProvider
     *
     * @param   string $file
     * @param   string $class
     */
    public function testMetadata($version, $file, $class = 'Metadata')
    {
        $client = self::$clients[$version];

        $this->assertInstanceOf("\\Vaites\\ApacheTika\\Metadata\\$class", $client->getMetadata($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @param   string $class
     */
    public function testDocumentMetadata($version, $file, $class = 'DocumentMetadata')
    {
        $this->testMetadata($version, $file, $class);
    }

    /**
     * Metadata title test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMetadataTitle($version, $file)
    {
        $client = self::$clients[$version];
        
        $this->assertEquals('Lorem ipsum dolor sit amet', $client->getMetadata($file)->title);
    }

    /**
     * Metadata author test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMetadataAuthor($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertEquals('David Martínez', $client->getMetadata($file)->author);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMetadataCreated($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertInstanceOf('DateTime', $client->getMetadata($file)->created);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMetadataUpdated($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertInstanceOf('DateTime', $client->getMetadata($file)->updated);
    }

    /**
     * Metadata keywords test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMetadataKeywords($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertContains('ipsum', $client->getMetadata($file)->keywords);
    }

    /**
     * Language test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentLanguage($version, $file)
    {
        if(version_compare($version, '1.9') >= 0)
        {
            $client = self::$clients[$version];

            $this->assertRegExp('/^[a-z]{2}$/', $client->getLanguage($file));
        }
        else
        {
            $this->markTestSkipped("Apache Tika $version lacks REST language identification");
        }
    }

    /**
     * MIME test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentMIME($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertNotEmpty($client->getMIME($file));
    }

    /**
     * HTML test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     */
    public function testDocumentHTML($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertContains('Zenonis est, inquam, hoc Stoici', $client->getHTML($file));
    }

    /**
     * Text test
     *
     * @dataProvider    fileProvider
     *
     * @param   string $file
     */
    public function testDocumentText($version, $file)
    {
        $client = self::$clients[$version];

        $this->assertContains('Zenonis est, inquam, hoc Stoici', $client->getText($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     * @param   string $class
     */
    public function testImageMetadata($version, $file, $class = 'ImageMetadata')
    {
        $this->testMetadata($version, $file, $class);
    }

    /**
     * Metadata width test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     */
    public function testImageMetadataWidth($version, $file)
    {
        $client = self::$clients[$version];

        $meta = $client->getMetadata($file);

        $this->assertEquals(1600, $meta->width, basename($file));
    }

    /**
     * Metadata height test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     */
    public function testImageMetadataHeight($version, $file)
    {
        $client = self::$clients[$version];

        $meta = $client->getMetadata($file);

        $this->assertEquals(900, $meta->height, basename($file));
    }

    /**
     * OCR test
     *
     * @dataProvider    ocrProvider
     *
     * @param   string $file
     */
    public function testImageOCR($version, $file)
    {
        $client = self::$clients[$version];

        $text = $client->getText($file);

        $this->assertRegExp('/voluptate/i', $text);
    }

    /**
     * Version test
     *
     * @dataProvider    versionProvider
     */
    public function testVersion($version)
    {
        $client = self::$clients[$version];

        $this->assertEquals("Apache Tika $version", $client->getVersion());
    }

    /**
     * Main file provider
     *
     * @return  array
     */
    public function fileProvider()
    {
        return $this->samples('sample1');
    }

    /**
     * Document file provider
     *
     * @return  array
     */
    public function documentProvider()
    {
        return $this->samples('sample1');
    }

    /**
     * Image file provider
     *
     * @return array
     */
    public function imageProvider()
    {
        return $this->samples('sample2');
    }

    /**
     * File provider for OCR
     *
     * @return array
     */
    public function ocrProvider()
    {
        return $this->samples('sample3');
    }

    /**
     * Compatible versions provider
     *
     * @return array
     */
    public function versionProvider()
    {
        $versions = [];

        foreach(Client::getSupportedVersions() as $version)
        {
            $versions[$version] = [$version];
        }

        return $versions;
    }

    /**
     * File provider using "samples" folder
     *
     * @param   string $sample
     *
     * @return  array
     */
    protected function samples($sample)
    {
        $samples = [];

        foreach(glob(dirname(__DIR__) . "/samples/$sample.*") as $sample)
        {
            foreach(Client::getSupportedVersions() as $version)
            {
                $samples[basename($sample) . " against v$version"] = [$version, $sample];
            }
        }

        return $samples;
    }
}
