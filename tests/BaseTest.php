<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests;

use DateTime;
use DateTimeZone;
use Exception;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Metadata;
use Vaites\ApacheTika\Metadata\Document;
use Vaites\ApacheTika\Metadata\Image;
use Vaites\ApacheTika\Clients\REST;

/**
 * Common test functionality
 */
abstract class BaseTest extends TestCase
{
    /**
     * Shared client instance
     */
    protected static Client $client;

    /**
     * Shared variable to test callbacks
     */
    public static int $shared = 0;

    /**
     * Version test
     */
    public function testVersion(): void
    {
        $this->assertStringContainsString(self::$version, self::$client->getVersion());
    }

    /**
     * Metadata test
     *
     * @dataProvider documentProvider
     */
    public function testMetadata(string $file, string $class = Metadata::class): void
    {
        $this->assertInstanceOf($class, self::$client->getMetadata($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadata(string $file, string $class = Document::Class): void
    {
        $this->testMetadata($file, $class);
    }

    /**
     * Metadata content
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataContent(string $file): void
    {
        $this->assertStringContainsString('Zenonis est, inquam, hoc Stoici', self::$client->getMetadata($file, true)->content);
    }

    /**
     * Metadata title test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataTitle(string $file): void
    {
        $this->assertEquals('Lorem ipsum dolor sit amet', self::$client->getMetadata($file)->title);
    }

    /**
     * Metadata author test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataAuthor(string $file): void
    {
        $this->assertEquals('David Martínez', self::$client->getMetadata($file)->author);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataCreated(string $file): void
    {
        $this->assertInstanceOf(DateTime::class, self::$client->getMetadata($file)->created);
    }

    /**
     * Metadata dates test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataUpdated(string $file): void
    {
        $this->assertInstanceOf(DateTime::class, self::$client->getMetadata($file)->updated);
    }

    /**
     * Metadata timezone test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataTimezone(string $file): void
    {
        $timezone = new DateTimeZone('Europe/Madrid');

        $metadata = self::$client->setTimezone($timezone->getName())->getMetadata($file);

        $this->assertEquals($metadata->updated->getTimezone()->getName(), $timezone->getName());
    }

    /**
     * Metadata keywords test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMetadataKeywords(string $file): void
    {
        $this->assertContains('ipsum', self::$client->getMetadata($file)->keywords);
    }

    /**
     * Recursive text metadata test
     *
     * @dataProvider recursiveProvider
     */
    public function testTextRecursiveMetadata(string $file): void
    {
        $nested = 'sample8.zip/sample1.doc';

        $metadata = self::$client->getRecursiveMetadata($file, 'text');

        $this->assertStringContainsString('Zenonis est, inquam, hoc Stoici', $metadata[$nested]->content ?? 'ERROR');
    }

    /**
     * Recursive HTML metadata test
     *
     * @dataProvider recursiveProvider
     */
    public function testHtmlRecursiveMetadata(string $file): void
    {
        $nested = 'sample8.zip/sample1.doc';

        $metadata = self::$client->getRecursiveMetadata($file, 'html');

        $this->assertStringContainsString('Zenonis est, inquam, hoc Stoici', $metadata[$nested]->content ?? 'ERROR');
    }

    /**
     * Recursive ignore metadata test
     *
     * @dataProvider ocrProvider
     */
    public function testIgnoreRecursiveMetadata(string $file): void
    {
        $metadata = self::$client->getRecursiveMetadata($file, 'ignore');

        $this->assertNull(array_shift($metadata)->content);
    }

    /**
     * Language test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentLanguage(string $file): void
    {
        $this->assertMatchesRegularExpression('/^[a-z]{2}$/', self::$client->getLanguage($file));
    }

    /**
     * MIME test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMIME(string $file): void
    {
        $this->assertNotEmpty(self::$client->getMIME($file));
    }

    /**
     * HTML test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentHTML(string $file): void
    {
        $this->assertStringContainsString('Zenonis est, inquam, hoc Stoici', self::$client->getHTML($file));
    }

    /**
     * Text test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentText(string $file): void
    {
        $this->assertStringContainsString('Zenonis est, inquam, hoc Stoici', self::$client->getText($file));
    }

    /**
     * Main text test
     *
     * @dataProvider documentProvider
     */
    public function testDocumentMainText(string $file): void
    {
        $this->assertStringContainsString('Lorem ipsum dolor sit amet', self::$client->getMainText($file));
    }

    /**
     * Metadata test
     *
     * @dataProvider imageProvider
     */
    public function testImageMetadata(string $file, string $class = Image::class): void
    {
        $this->testMetadata($file, $class);
    }

    /**
     * Metadata width test
     *
     * @dataProvider imageProvider
     */
    public function testImageMetadataWidth(string $file): void
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals(1600, $meta->width, basename($file));
    }

    /**
     * Metadata height test
     *
     * @dataProvider imageProvider
     */
    public function testImageMetadataHeight(string $file): void
    {
        $meta = self::$client->getMetadata($file);

        $this->assertEquals(900, $meta->height, basename($file));
    }

    /**
     * OCR test
     *
     * @dataProvider ocrProvider
     */
    public function testImageOCR(string $file): void
    {
        $text = self::$client->getText($file);

        $this->assertMatchesRegularExpression('/voluptate/i', $text);
    }

    /**
     * Text callback test
     *
     * @dataProvider callbackProvider
     */
    public function testTextCallback(string $file): void
    {
        BaseTest::$shared = 0;

        self::$client->getText($file, [$this, 'callableCallback']);

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Text callback test
     *
     * @dataProvider callbackProvider
     */
    public function testTextCallbackWithoutAppend(string $file): void
    {
        BaseTest::$shared = 0;

        $response = self::$client->getText($file, [$this, 'callableCallback'], false);

        $this->assertEmpty($response);
    }

    /**
     * Main text callback test
     *
     * @dataProvider callbackProvider
     */
    public function testMainTextCallback(string $file): void
    {
        BaseTest::$shared = 0;

        self::$client->getMainText($file, fn() => BaseTest::$shared++);

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Main text callback test
     *
     * @dataProvider callbackProvider
     */
    public function testHtmlCallback(string $file): void
    {
        BaseTest::$shared = 0;

        self::$client->getHtml($file, fn() => BaseTest::$shared++);

        $this->assertGreaterThan(1, BaseTest::$shared);
    }

    /**
     * Remote file test with integrated download
     *
     * @dataProvider remoteProvider
     */
    public function testRemoteDocumentText(string $file): void
    {
        $this->assertStringContainsString('Rationis enim perfectio est virtus', self::$client->getText($file));
    }

    /**
     * Remote file test with internal downloader
     *
     * @dataProvider remoteProvider
     */
    public function testDirectRemoteDocumentText(string $file): void
    {
        if(self::$client instanceof REST && version_compare(self::$version, '2.0') >= 0)
        {
            $this->markTestSkipped('Apache Tika 2.0 server does not support remote documents yet');
        }
        else
        {
            $client =& self::$client;
            $client->setDownloadRemote(false);

            $this->assertStringContainsString('Rationis enim perfectio est virtus', $client->getText($file));
        }
    }

    /**
     * Encoding tests
     *
     * @dataProvider encodingProvider
     */
    public function testEncodingDocumentText(string $file): void
    {
        $client =& self::$client;

        $client->setEncoding('UTF-8');

        $this->assertThat($client->getText($file), $this->logicalAnd
        (
            $this->stringContains('L’espéranto'),
            $this->stringContains('世界語'),
            $this->stringContains('Эспера́нто')
        ));
    }

    /**
     * Test available detectors
     */
    public function testAvailableDetectors(): void
    {
        $detectors = self::$client->getAvailableDetectors();

        $this->assertArrayHasKey('org.apache.tika.detect.DefaultDetector', $detectors);
    }

    /**
     * Test available parsers
     */
    public function testAvailableParsers(): void
    {
        $parsers = self::$client->getAvailableParsers();

        $this->assertArrayHasKey('org.apache.tika.parser.DefaultParser', $parsers);
    }

    /**
     * Test supported MIME types
     */
    public function testSupportedMIMETypes(): void
    {
        $this->assertArrayHasKey('application/pdf', self::$client->getSupportedMIMETypes());
    }


    /**
     * Test supported MIME types
     */
    public function testIsMIMETypeSupported(): void
    {
        $this->assertTrue(self::$client->isMIMETypeSupported('application/pdf'));
    }

    /**
     * Static method to test callback
     */
    public static function callableCallback(): void
    {
        BaseTest::$shared++;
    }

    /**
     * Document file provider
     */
    public function documentProvider(): array
    {
        return $this->samples('sample1');
    }

    /**
     * Image file provider
     */
    public function imageProvider(): array
    {
        return $this->samples('sample2');
    }

    /**
     * File provider for OCR testing
     */
    public function ocrProvider(): array
    {
        return $this->samples('sample3');
    }

    /**
     * File provider for callback testing
     */
    public function callbackProvider(): array
    {
        return $this->samples('sample5');
    }

    /**
     * File provider for remote testing
     */
    public function remoteProvider(): array
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
     */
    public function encodingProvider(): array
    {
        return $this->samples('sample7');
    }

    /**
     * File provider for recursive testing
     */
    public function recursiveProvider(): array
    {
        return $this->samples('sample8');
    }

    /**
     * File provider using "samples" folder
     */
    protected function samples(string $sample): array
    {
        $samples = [];

        foreach(glob(dirname(__DIR__) . "/samples/$sample*") as $sample)
        {
            $samples[basename($sample)] = [$sample];
        }

        return $samples;
    }
}
