<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Tests\Legacy;

use Vaites\ApacheTika\Clients\REST;

/**
 * Base test case
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
   /**
     * Current tika version
     */
    protected static string $version;

    /**
     * Binary path (jars)
     */
    protected static string $binaries;

    /**
     * Set version and binary path
     */
    public function __construct(string $name = null, array $data = array(), $dataName = '')
    {
        self::$version = getenv('APACHE_TIKA_VERSION') ?: $this->latestVersion();
        self::$binaries = getenv('APACHE_TIKA_BINARIES') ?: 'bin';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Get the latest supported version
     */
    protected function latestVersion(): string
    {
        return REST::make('localhost', 9998, [], false)->getLatestSupportedVersion();
    }

    /**
     * Get the full path of Tika app for a specified version
     */
    protected static function getPathForVersion(string $version): string
    {
        return self::$binaries . "/tika-app-{$version}.jar";
    }
}