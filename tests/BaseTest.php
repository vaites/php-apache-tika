<?php

namespace Vaites\ApacheTika\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Common test functionality
 */
abstract class BaseTest extends PHPUnit_Framework_TestCase
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
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        self::$version = getenv('APACHE_TIKA_VERSION');
        self::$binaries = getenv('APACHE_TIKA_BINARIES');

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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertInstanceOf("\\Vaites\\ApacheTika\\Metadata\\$class", self::$client->getMetadata($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->testMetadata($file, $class);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertEquals('Lorem ipsum dolor sit amet', self::$client->getMetadata($file)->title);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertEquals('David Martínez', self::$client->getMetadata($file)->author);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertInstanceOf('DateTime', self::$client->getMetadata($file)->created);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertInstanceOf('DateTime', self::$client->getMetadata($file)->updated);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertContains('ipsum', self::$client->getMetadata($file)->keywords);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') < 0)
        {
            $this->markTestSkipped('Apache Tika ' . self::$version . ' lacks REST language identification');
        }
        else
        {
            $this->assertRegExp('/^[a-z]{2}$/', self::$client->getLanguage($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertNotEmpty(self::$client->getMIME($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertContains('Zenonis est, inquam, hoc Stoici', self::$client->getHTML($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertContains('Zenonis est, inquam, hoc Stoici', self::$client->getText($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.15') < 0)
        {
            $this->markTestSkipped('Apache Tika ' . self::$version . ' lacks main content extraction');
        }
        else
        {
            $this->assertContains('Lorem ipsum dolor sit amet', self::$client->getMainText($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        elseif($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.14 throws "Expected \';\', got \',\'> when parsing some images');
        }
        else
        {
            $this->testMetadata($file, $class);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        elseif($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.14 throws "Expected \';\', got \',\'> when parsing some images');
        }
        else
        {
            $meta = self::$client->getMetadata($file);

            $this->assertEquals(1600, $meta->width, basename($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        elseif($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.14 throws "Expected \';\', got \',\'> when parsing some images');
        }
        else
        {
            $meta = self::$client->getMetadata($file);

            $this->assertEquals(900, $meta->height, basename($file));
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        elseif($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.14 throws "Expected \';\', got \',\'> when parsing some images');
        }
        else
        {
            $text = self::$client->getText($file);

            $this->assertRegExp('/voluptate/i', $text);
        }
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
        $client =& self::$client;
        
    if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            BaseTest::$shared = 0;

            self::$client->getText($file, [$this, 'callableCallback']);

            $this->assertGreaterThan(1, BaseTest::$shared);
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.15') < 0)
        {
            $this->markTestSkipped('Apache Tika ' . self::$version . ' lacks main content extraction');
        }
        else
        {
            BaseTest::$shared = 0;

            self::$client->getMainText($file, function ($chunk) {
                BaseTest::$shared++;
            });

            $this->assertGreaterThan(1, BaseTest::$shared);
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
    public function testHtmlCallback($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            BaseTest::$shared = 0;

            self::$client->getHtml($file, function($chunk)
            {
                BaseTest::$shared++;
            });

            $this->assertGreaterThan(1, BaseTest::$shared);
        }
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
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        else
        {
            $this->assertContains('This is a small demonstration .pdf file', $client->getText($file));
        }
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

        if($client::MODE == 'web' && version_compare(self::$version, '1.9') == 0)
        {
            $this->markTestSkipped('Apache Tika 1.9 throws random "Error while processing document" errors');
        }
        elseif($client::MODE == 'web' && version_compare(self::$version, '1.15') < 0)
        {
            $this->markTestSkipped('Apache Tika between 1.10 and 1.14 doesn\'t supports unsecure features');
        }
        else
        {
            $client->setDownloadRemote(false);

            $this->assertContains('This is a small demonstration .pdf file', $client->getText($file));
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
        $this->assertContains('application/x-pdf', self::$client->getSupportedMIMETypes());
    }

    /**
     * Static method to test callback
     *
     * @param   string  $chunk
     */
    public static function callableCallback($chunk)
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
                'http://www.africau.edu/images/default/sample.pdf'
            ]
        ];
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
