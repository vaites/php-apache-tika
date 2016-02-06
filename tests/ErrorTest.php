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
     * Test wrong server
     */
    public function testTikaConnection()
    {
        try
        {
            Client::make('localhost', 9999);

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Failed to connect to localhost', $exception->getMessage());
        }
    }

    /**
     * Test wrong command line mode path
     */
    public function testTikaPath()
    {
        try
        {
            Client::make('/nonexistent/path/to/apache-tika.jar');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('Apache Tika JAR not found', $exception->getMessage());
        }
    }

    /**
     * Test nonexistent local file
     */
    public function testLocalFile()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getText('/nonexistent/path/to/file.pdf');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('file.pdf can\'t be opened', $exception->getMessage());
        }
    }

    /**
     * Test nonexistent remote file
     */
    public function testRemoteFile()
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getText('http://localhost/nonexistent/path/to/file.pdf');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertContains('failed to open stream', $exception->getMessage());
        }
    }

    /**
     * Test wrong request type
     */
    public function testRequestType()
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
            $this->assertContains('Failed to connect to localhost', $exception->getMessage());
        }
    }
}