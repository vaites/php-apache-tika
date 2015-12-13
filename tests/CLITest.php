<?php namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for command line mode
 */
class CLITest extends BaseTest
{
    /**
     * Create shared instance of client
     */
    public static function setUpBeforeClass()
    {
        $travis = '/home/' . trim(shell_exec('whoami')) . '/tika/tika-app-1.11.jar';
        $develop = dirname(__DIR__) . '/bin/tika-app-1.11.jar';

        self::$client = Client::make(file_exists($travis) ? $travis : $develop);
    }
}