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
     */
    public function testImageMetadata($file, $class = 'ImageMetadata')
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
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
     */
    public function testImageMetadataWidth($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
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
     */
    public function testImageMetadataHeight($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
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
     */
    public function testImageOCR($file)
    {
        $client =& self::$client;

        if($client::MODE == 'web' && version_compare(self::$version, '1.14') == 0)
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
     */
    public function testTextCallback($file)
    {
        BaseTest::$shared = 0;

        self::$client->getText($file, [$this, 'callableCallback']);

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Main text callback test
     *
     * @dataProvider    callbackProvider
     *
     * @param   string $file
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
     */
    public function testHtmlCallback($file)
    {
        BaseTest::$shared = 0;

        self::$client->getHtml($file, function($chunk)
        {
            BaseTest::$shared++;
        });

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Remote file test
     *
     * @dataProvider    remoteProvider
     *
     * @param   string $file
     */
    public function testRemoteDocumentText($file)
    {
        $this->assertContains('This is a small demonstration .pdf file', self::$client->getText($file));
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
