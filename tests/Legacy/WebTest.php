<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests\Legacy;

use Vaites\ApacheTika\Legacy\REST as Client;

/**
 * Tests for web mode
 */
class WebTest extends BaseTest
{
    /**
     * Start Tika server and create shared instance of clients
     */
    public static function setUpBeforeClass(): void
    {
        self::$client = new Client('localhost', 9998, [CURLOPT_TIMEOUT => 30]);
    }

    /**
     * @testdox Custom header can be set
     */
    public function testHttpHeader(): void
    {
        $client = (new Client('localhost', 9998))->setHeader('Foo', 'bar');

        $this->assertEquals('bar', $client->getHeader('foo'));
    }

    /**
     * @testdox Single OCR language can be set
     */
    public function testOCRLanguage(): void
    {
        $client = (new Client('localhost', 9998))->setOCRLanguage('spa');

        $this->assertEquals(['spa'], $client->getOCRLanguages());
    }

    /**
     * @testdox Multiple OCR languages can be set
     */
    public function testOCRLanguages(): void
    {
        $client = (new Client('localhost', 9998))->setOCRLanguages(['fra', 'spa']);

        $this->assertEquals(['fra', 'spa'], $client->getOCRLanguages());
    }

    /**
     * @testdox Single cURL options can be set
     */
    public function testCurlSingleOption(): void
    {
        $client = (new Client('localhost', 9998))->setOption(CURLOPT_TIMEOUT, 3);

        $this->assertEquals(3, $client->getOption(CURLOPT_TIMEOUT));
    }

    /**
     * @testdox Multiple cURL options can be set
     */
    public function testCurlOptions(): void
    {
        $client = new Client('localhost', 9998, [CURLOPT_TIMEOUT => 3]);
        $options = $client->getOptions();

        $this->assertEquals(3, $options[CURLOPT_TIMEOUT]);
    }

    /**
     * @testdox cURL timeout can be set
     */
    public function testCurlTimeoutOption(): void
    {
        $client = (new Client('localhost', 9998))->setTimeout(3);

        $this->assertEquals(3, $client->getTimeout());
    }

    /**
     * @testdox cURL header can be set
     */
    public function testCurlHeaders(): void
    {
        $header = 'Content-Type: image/jpeg';

        $client = new Client('localhost', 9998, [CURLOPT_HTTPHEADER => [$header]]);
        $options = $client->getOptions();

        $this->assertContains($header, $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * @testdox Host can be set
     */
    public function testSetHost(): void
    {
        $client = new Client('localhost', 9998);
        $client->setHost('127.0.0.1');

        $this->assertEquals('127.0.0.1', $client->getHost());
    }

    /**
     * @testdox Port can be set
     */
    public function testSetPort(): void
    {
        $client = new Client('localhost', 9998);
        $client->setPort(9997);

        $this->assertEquals(9997, $client->getPort());
    }

    /**
     * @testdox Host can be set using an URL
     */
    public function testSetUrlHost(): void
    {
        $client = new Client('http://localhost:9998');

        $this->assertEquals('localhost', $client->getHost());
    }

    /**
     * @testdox Port can be set using an URL
     */
    public function testSetUrlPort(): void
    {
        $client = new Client('http://localhost:9998');

        $this->assertEquals(9998, $client->getPort());
    }

    /**
     * @testdox Retries can be set using
     */
    public function testSetRetries(): void
    {
        $client = new Client('localhost', 9998);
        $client->setRetries(5);

        $this->assertEquals(5, $client->getRetries());
    }

    /**
     * @testdox Version check can be delayed
     */
    public function testDelayedCheck(): void
    {
        $client = new Client('localhost', 9997, [], false);
        $client->setPort(9998);

        $this->assertStringContainsString(self::$version, $client->getVersion());
    }
}