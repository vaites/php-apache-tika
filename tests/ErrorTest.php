<?php namespace Vaites\ApacheTika\Tests;

use Exception;
use PHPUnit_Framework_TestCase;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Metadata\Metadata;

/**
 * Error tests
 */
class ErrorTest extends PHPUnit_Framework_TestCase
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
     * Test wrong command line mode path
     */
    public function testAppPath()
    {
        try
        {
            $client = Client::make('/nonexistent/path/to/apache-tika.jar');
            $client->getVersion();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unexpected exit value', $exception->getMessage());
        }
    }

    /**
     * Test unexpected exit value for command line mode
     */
    public function testAppExitValue()
    {
        $path = self::$binaries . '/tika-app-' . self::$version . '.jar';

        try
        {
            $client = Client::make($path);

            rename($path, $path . '.bak');

            $client->getVersion();
        }
        catch(Exception $exception)
        {
            rename($path . '.bak', $path);

            $this->assertContains('Unexpected exit value', $exception->getMessage());
        }
    }

    /**
     * Test invalid Java binary path for command line mode
     */
    public function testAppJavaBinary()
    {
        $path = self::$binaries . '/tika-app-' . self::$version . '.jar';

        try
        {
            $client = Client::make($path, '/nonexistent/path/to/java');
            $client->getVersion();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unexpected exit value', $exception->getMessage());
        }
    }

    /**
     * Test wrong server
     */
    public function testServerConnection()
    {
        try
        {
            Client::make('localhost', 9997);

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(7, $exception->getCode());
        }
    }

    /**
     * Test wrong request options
     */
    public function testRequestOptions()
    {
        try
        {
            $client = Client::make('localhost', 9998, [CURLOPT_PROXY => 'localhost']);
            $client->request('bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(7, $exception->getCode());
        }
    }

    /**
     * Test unsupported media type
     */
    public function testUnsupportedMedia()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getText(dirname(__DIR__) . '/samples/sample4.doc');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(415, $exception->getCode());
        }
    }

    /**
     * Test invalid callback
     */
    public function testInvalidCallback()
    {
        try
        {
            $client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar');
            $client->setCallback('unknown_function');
        }
        catch(Exception $exception)
        {
            $this->assertContains('Invalid callback', $exception->getMessage());
        }
    }

    /**
     * Test invalid chunk size
     */
    public function testInvalidChunkSize()
    {
        try
        {
            $client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar');
            $client->setChunkSize('string');
        }
        catch(Exception $exception)
        {
            $this->assertContains('is not a valid chunk size', $exception->getMessage());
        }
    }

    /**
     * Test invalid chunk size
     */
    public function testUnsupportedChunkSize()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->setChunkSize(1024);
        }
        catch(Exception $exception)
        {
            $this->assertContains('Chunk size is not supported', $exception->getMessage());
        }
    }

    /**
     * Test invalid metadata
     */
    public function testInvalidMetadata()
    {
        try
        {
            $metadata = Metadata::make('InvalidJsonString', './samples/sample1.doc');
        }
        catch(Exception $exception)
        {
            $this->assertEquals(JSON_ERROR_SYNTAX, $exception->getCode());
        }
    }

    /**
     * Test empty metadata
     */
    public function testEmptyMetadata()
    {
        try
        {
            $metadata = Metadata::make('', './samples/sample1.doc');
        }
        catch(Exception $exception)
        {
            $this->assertContains('Empty response', $exception->getMessage());
        }
    }

    /**
     * Test nonexistent local file for all clients
     *
     * @dataProvider    parameterProvider
     * 
     * @param   array   $parameters
     */
    public function testLocalFile($parameters)
    {
        try
        {
            $client = call_user_func_array(['Vaites\ApacheTika\Client', 'make'], $parameters);
            $client->getText('/nonexistent/path/to/file.pdf');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(0, $exception->getCode());
        }
    }

    /**
     * Test nonexistent remote file for all clients
     *
     * @dataProvider    parameterProvider
     *
     * @param   array   $parameters
     */
    public function testRemoteFile($parameters)
    {
        try
        {
            $client = call_user_func_array(['Vaites\ApacheTika\Client', 'make'], $parameters);
            $client->getText('http://localhost/nonexistent/path/to/file.pdf');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(2, $exception->getCode());
        }
    }

    /**
     * Test wrong request type for all clients
     *
     * @dataProvider    parameterProvider
     *
     * @param   array   $parameters
     */
    public function testRequestType($parameters)
    {
        try
        {
            $client = call_user_func_array(['Vaites\ApacheTika\Client', 'make'], $parameters);
            $client->request('bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unknown type bad', $exception->getMessage());
        }
    }

    /**
     * Client parameters provider
     *
     * @return array
     */
    public function parameterProvider()
    {
        return
        [
            [[self::$binaries . '/tika-app-' . self::$version . '.jar']],
            [['localhost', 9998]]
        ];
    }
}