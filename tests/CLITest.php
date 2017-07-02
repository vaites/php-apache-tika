<?php

namespace Vaites\ApacheTika\Tests;

use Vaites\ApacheTika\Client;

/**
 * Tests for command line mode
 */
class CLITest extends BaseTest
{
    /**
     * Create shared instances of clients
     */
    public static function setUpBeforeClass()
    {
        self::$client = Client::make(self::$binaries . '/tika-app-' . self::$version . '.jar');
    }
}