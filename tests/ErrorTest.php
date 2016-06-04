<?php namespace Vaites\ApacheTika\Tests;

use Exception;
use PHPUnit_Framework_TestCase;

use Vaites\ApacheTika\Client;

/**
 * Error tests
 */
class ErrorTest extends PHPUnit_Framework_TestCase
{
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
        $path = getenv('APACHE_TIKA_JARS') . '/tika-app-' . getenv('APACHE_TIKA_VERSION') . '.jar';

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
        $path = getenv('APACHE_TIKA_JARS') . '/tika-app-' . getenv('APACHE_TIKA_VERSION') . '.jar';

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
            Client::make('localhost', 9999);

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
     * Test nonexistent local file for all clients
     *
     * @dataProvider    clientProvider
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
     * @dataProvider    clientProvider
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
     * @dataProvider    clientProvider
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
    public function clientProvider()
    {
        return
        [
            [[getenv('APACHE_TIKA_JARS') . '/tika-app-' . getenv('APACHE_TIKA_VERSION') . '.jar']],
            [['localhost', 9998]]
        ];
    }
}