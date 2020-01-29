<?php

namespace Vaites\ApacheTika\Tests;

use Exception;

use PHPUnit\Framework\TestCase;

/**
 * Common test functionality
 */
abstract class BaseTest extends TestCase
{
    /**
     * Current tika version
     *
     * @var string
     */
    protected static $version = null;

    /**
     * Binary path (jars)
     *
     * @var string
     */
    protected static $binaries = null;

    /**
     * Shared client instance
     *
     * @var \Vaites\ApacheTika\Client
     */
    protected static $client = null;

    /**
     * Shared variable to test callbacks
     *
     * @var mixed
     */
    public static $shared = null;

    /**
     * Get env variables
     *
     * @param null      $name
     * @param array     $data
     * @param string    $dataName
     * @throws \Exception
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        self::$version = getenv('APACHE_TIKA_VERSION');
        self::$binaries = getenv('APACHE_TIKA_BINARIES');

        if(empty(self::$version))
        {
            throw new Exception('APACHE_TIKA_VERSION environment variable not defined');
        }

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Version test
     */
    public function testVersion()
    {
        $this->assertEquals('Apache Tika ' . self::$version, self::$client->getVersion());
    }

    /**
     * Metadata test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @param   string $class
     * @throws  \Exception
     */
    public function testMetadata($file, $class = 'Metadata')
    {
        $this->assertInstanceOf("\\Vaites\\ApacheTika\\Metadata\\$class", self::$client->getMetadata($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @param   string $class
     * @throws  \Exception
     */
    public function testDocumentMetadata($file, $class = 'DocumentMetadata')
    {
        $this->testMetadata($file, $class);
    }

    /**
     * Metadata title test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMetadataTitle($file)
    {
        $this->assertEquals('Lorem ipsum dolor sit amet', self::$client->getMetadata($file)->title);
    }

    /**
     * Metadata author test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMetadataAuthor($file)
    {
        $this->assertEquals('David Martínez', self::$client->getMetadata($file)->author);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMetadataCreated($file)
    {
        $this->assertInstanceOf('DateTime', self::$client->getMetadata($file)->created);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMetadataUpdated($file)
    {
        $this->assertInstanceOf('DateTime', self::$client->getMetadata($file)->updated);
    }

    /**
     * Metadata keywords test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMetadataKeywords($file)
    {
        $this->assertContains('ipsum', self::$client->getMetadata($file)->keywords);
    }

    /**
     * Language test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentLanguage($file)
    {
        $this->assertRegExp('/^[a-z]{2}$/', self::$client->getLanguage($file));
    }

    /**
     * MIME test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMIME($file)
    {
        $this->assertNotEmpty(self::$client->getMIME($file));
    }

    /**
     * HTML test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentHTML($file)
    {
        $this->assertContains('Zenonis est, inquam, hoc Stoici', self::$client->getHTML($file));
    }

    /**
     * Text test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentText($file)
    {
        $this->assertContains('Zenonis est, inquam, hoc Stoici', self::$client->getText($file));
    }

    /**
     * Main text test
     *
     * @dataProvider    documentProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDocumentMainText($file)
    {
        $this->assertContains('Lorem ipsum dolor sit amet', self::$client->getMainText($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     * @param   string $class
     * @throws  \Exception
     */
    public function testImageMetadata($file, $class = 'ImageMetadata')
    {
        $this->testMetadata($file, $class);
    }

    /**
     * Metadata width test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testImageMetadataWidth($file)
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals(1600, $meta->width, basename($file));
    }

    /**
     * Metadata height test
     *
     * @dataProvider    imageProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testImageMetadataHeight($file)
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals(900, $meta->height, basename($file));
    }

    /**
     * OCR test
     *
     * @dataProvider    ocrProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testImageOCR($file)
    {
        $text = self::$client->getText($file);

        $this->assertRegExp('/voluptate/i', $text);
    }

    /**
     * Text callback test
     *
     * @dataProvider    callbackProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testTextCallback($file)
    {
        BaseTest::$shared = 0;

        self::$client->getText($file, [$this, 'callableCallback']);

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Text callback test
     *
     * @dataProvider    callbackProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testTextCallbackWithoutAppend($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            BaseTest::$shared = 0;

            $response = self::$client->getText($file, [$this, 'callableCallback'], false);

            $this->assertEmpty($response);
        }
    }

    /**
     * Main text callback test
     *
     * @dataProvider    callbackProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testMainTextCallback($file)
    {
        BaseTest::$shared = 0;

        self::$client->getMainText($file, function()
        {
            BaseTest::$shared++;
        });

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Main text callback test
     *
     * @dataProvider    callbackProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testHtmlCallback($file)
    {
        BaseTest::$shared = 0;

        self::$client->getHtml($file, function()
        {
            BaseTest::$shared++;
        });

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Remote file test with integrated download
     *
     * @dataProvider    remoteProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testRemoteDocumentText($file)
    {
        $this->assertContains('Rationis enim perfectio est virtus', self::$client->getText($file));
    }

    /**
     * Remote file test with internal downloader
     *
     * @dataProvider    remoteProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testDirectRemoteDocumentText($file)
    {
        $client =& self::$client;

        $client->setDownloadRemote(false);

        $this->assertContains('Rationis enim perfectio est virtus', $client->getText($file));
    }

    /**
     * Encoding tests
     *
     * @dataProvider    encodingProvider
     *
     * @param   string $file
     * @throws  \Exception
     */
    public function testEncodingDocumentText($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            //$client->setEncoding('UTF-8');

            $this->assertThat($client->getText($file), $this->logicalAnd
            (
                $this->stringContains('L’espéranto'),
                $this->stringContains('世界語'),
                $this->stringContains('Эспера́нто')
            ));
        }
    }

    /**
     * Test available detectors
     *
     * @throws  \Exception
     */
    public function testAvailableDetectors()
    {
        $this->assertContains('org.apache.tika.mime.MimeTypes', self::$client->getAvailableDetectors());
    }

    /**
     * Test available parsers
     *
     * @throws  \Exception
     */
    public function testAvailableParsers()
    {
        $this->assertContains('org.apache.tika.parser.DefaultParser', self::$client->getAvailableParsers());
    }

    /**
     * Test supported MIME types
     *
     * @throws  \Exception
     */
    public function testSupportedMIMETypes()
    {
        $this->assertArrayHasKey('application/pdf', self::$client->getSupportedMIMETypes());
    }

    /**
     * Static method to test callback
     */
    public static function callableCallback()
    {
        BaseTest::$shared++;
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
     * File provider for OCR testing
     *
     * @return array
     */
    public function ocrProvider()
    {
        return $this->samples('sample3');
    }

    /**
     * File provider for callback testing
     *
     * @return array
     */
    public function callbackProvider()
    {
        return $this->samples('sample5');
    }

    /**
     * File provider for remote testing
     *
     * @return array
     */
    public function remoteProvider()
    {
        return
        [
            [
                'https://raw.githubusercontent.com/vaites/php-apache-tika/master/samples/sample6.pdf'
            ]
        ];
    }

    /**
     * File provider for encoding testing
     *
     * @return array
     */
    public function encodingProvider()
    {
        return $this->samples('sample7');
    }

    /**
     * File provider using "samples" folder
     *
     * @param   string $sample
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
}
