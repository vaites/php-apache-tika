<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Legacy;

use Vaites\ApacheTika\Clients\REST as Client;

/**
 * Keep compatibility with old WebClient class
 *
 * @method static REST make(string $host = 'localhost', int $port = 9998, array $options = [], bool $check = true)
 */
class REST extends Client
{
    /**
     * Apache Tika server host
     */
    protected string $host = 'localhost';

    /**
     * Apache Tika server port
     */
    protected int $port = 9998;

    /**
     * Constructor compatible with old REST class
     */
    public function __construct(string $host = null, int $port = null, array $options = [], bool $check = true)
    {
        $url = filter_var($host, FILTER_VALIDATE_URL) ? $host : "http://$host:$port";

        parent::__construct($url, $options, $check);
    }

    /**
     * Get Apache Tika server host
     */
    public function getHost(): string
    {
        return (string) parse_url($this->url, PHP_URL_HOST);
    }

    /**
     * Set Apache Tika server host
     */
    public function setHost(string $host): self
    {
        $this->setUrl(filter_var($host, FILTER_VALIDATE_URL) ? $host : "http://$host:{$this->port}");

        return $this;
    }

    /**
     * Get Apache Tika server port
     */
    public function getPort(): int
    {
        return (int) parse_url($this->url, PHP_URL_PORT);
    }

    /**
     * Set Apache Tika server port
     */
    public function setPort(int $port): self
    {
        $this->setUrl("{$this->host}:$port");

        return $this;
    }
}