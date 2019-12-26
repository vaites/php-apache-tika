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
            $client = Client::prepare('/nonexistent/path/to/apache-tika.jar');
            $client->getVersion();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Apache Tika app JAR not found', $exception->getMessage());
        }
    }

    /**
     * Test unexpected exit value for command line mode
     */
    public function testAppExitValue()
    {
        $path = self::getPathForVersion(self::$version);

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
    public function testJavaBinary()
    {
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = Client::make($path, '/nonexistent/path/to/java');
            $client->getVersion();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Java command not found', $exception->getMessage());
        }
    }

    /**
     * Test wrong server
     */
    public function testServerConnection()
    {
        try
        {
            $client = Client::prepare('localhost', 9997);
            $client->getVersion();

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
            $client = Client::make('localhost', 9998);
            $client->request('bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unknown type bad', $exception->getMessage());
        }
    }

    /**
     * Test invalidrequest options
     */
    public function testRequestRestrictedOptions()
    {
        try
        {
            Client::make('localhost', 9998, [CURLOPT_PUT => false]);
        }
        catch(Exception $exception)
        {
            $this->assertEquals(3, $exception->getCode());
        }
    }

    /**
     * Test wrong recursive metadata type
     */
    public function testRequestMetadataType()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getMetadata(dirname(__DIR__) . '/samples/sample3.png', 'bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unknown recursive type', $exception->getMessage());
        }
    }

    /**
     * Test unsupported media type
     *
     * NOTE: return value was changed in version 1.23
     *
     * @link    https://github.com/apache/tika/blob/master/CHANGES.txt
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
            if(version_compare(self::$version, '1.23') < 0)
            {
                $this->assertEquals(415, $exception->getCode());
            }
            else
            {
                $this->assertEquals(0, $exception->getCode());
            }
        }
    }

    /**
     * Test unsupported command line recursive metadata
     */
    public function testUnsupportedCLIRecursiveMetadata()
    {
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = Client::make($path);
            $client->getMetadata('example.doc', 'html');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Recursive metadata is not supported', $exception->getMessage());
        }
    }

    /**
     * Test unknown recursive metadata type
     */
    public function testUnknownRecursiveMetadataType()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getMetadata('example.doc', 'error');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Unknown recursive type', $exception->getMessage());
        }
    }

    /**
     * Test invalid callback
     */
    public function testInvalidCallback()
    {
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = Client::make($path);
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
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = Client::make($path);
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
            Metadata::make('InvalidJsonString', './samples/sample1.doc');
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
            Metadata::make('', './samples/sample1.doc');
        }
        catch(Exception $exception)
        {
            $this->assertContains('Empty response', $exception->getMessage());
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
     * Client parameters provider
     *
     * @return array
     */
    public function parameterProvider()
    {
        return
        [
            [[self::getPathForVersion(self::$version)]],
            [['localhost', 9998]]
        ];
    }

    /**
     * Get the full path of Tika app for a specified version
     *
     * @param   string  $version
     * @return  string
     */
    private static function getPathForVersion($version)
    {
        return self::$binaries . "/tika-app-{$version}.jar";
    }
}