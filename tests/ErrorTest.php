<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests;

use Exception;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Clients\CLI;
use Vaites\ApacheTika\Metadata;

/**
 * Error tests
 */
class ErrorTest extends TestCase
{
    /**
     * @testdox Invalid JAR path must throw an exception
     */
    public function testAppPath(): void
    {
        try
        {
            $client = Client::prepare('/nonexistent/path/to/apache-tika.jar');
            $client->getVersion();

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Apache Tika app JAR not found', $exception->getMessage());
        }
    }

    /**
     * @testdox Invalid JAR file must throw an exception
     */
    public function testAppExitValue(): void
    {
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = CLI::prepare(__FILE__);
            $client->getVersion(true);

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Unexpected exit value', $exception->getMessage());
        }
    }

    /**
     * @testdox Invalid Java binary must throw an exception
     */
    public function testJavaBinary(): void
    {
        $path = self::getPathForVersion(self::$version);

        try
        {
            $client = CLI::prepare($path, '/nonexistent/path/to/java');
            $version = $client->getVersion(true);

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Java command not found', $exception->getMessage());
        }
    }

    /**
     * @testdox Invalid server host and port must throw an exception
     */
    public function testServerConnection(): void
    {
        try
        {
            $client = Client::prepare('localhost', 9997);
            $client->getVersion();

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertThat($exception->getCode(), $this->logicalOr
            (
                $this->equalTo(CURLE_COULDNT_CONNECT),
                $this->equalTo(CURLE_OPERATION_TIMEDOUT)
            ));
        }
    }

    /**
     * @testdox Invalid request options must throw an exception
     */
    public function testRequestRestrictedOptions(): void
    {
        try
        {
            Client::make('localhost', 9998, [CURLOPT_PUT => false]);

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertEquals(3, $exception->getCode());
        }
    }

    /**
     * @testdox Invalid non recursive file must throw an exception
     */
    public function testRequestMetadataType(): void
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getRecursiveMetadata(dirname(__DIR__) . '/samples/sample3.png', 'bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Unknown recursive type', $exception->getMessage());
        }
    }

    /**
     * @testdox Unsupported media must throw an exception
     *
     * NOTE: return value was changed in version 1.23
     *
     * @link    https://github.com/apache/tika/blob/master/CHANGES.txt
     */
    public function testUnsupportedMedia(): void
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
     * @testdox Invalid recursive format must throw an exception
     */
    public function testUnknownRecursiveMetadataType(): void
    {
        try
        {
            $client = Client::make('localhost', 9998);
            $client->getRecursiveMetadata('example.doc', 'error');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Unknown recursive type', $exception->getMessage());
        }
    }

    /**
     * @testdox Invalid request type must throw an exception
     *
     * @dataProvider    parameterProvider
     */
    public function testRequestType(array $parameters): void
    {
        try
        {
            $client = call_user_func_array(['Vaites\ApacheTika\Client', 'make'], $parameters);
            $client->request('bad');

            $this->fail();
        }
        catch(Exception $exception)
        {
            $this->assertStringContainsString('Unknown type bad', $exception->getMessage());
        }
    }

    /**
     * @testdox Non existent file must throw an exception
     *
     * @dataProvider    parameterProvider
     */
    public function testLocalFile(array $parameters): void
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
     * @testdox Non existent URL must throw an exception
     *
     * @dataProvider    parameterProvider
     */
    public function testRemoteFile(array $parameters): void
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
     */
    public function parameterProvider(): array
    {
        return
        [
            [[self::getPathForVersion(self::$version)]],
            [['localhost', 9998]]
        ];
    }
}