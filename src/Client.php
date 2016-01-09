<?php namespace Vaites\ApacheTika;

use Vaites\ApacheTika\Clients\CLIClient;
use Vaites\ApacheTika\Clients\WebClient;

/**
 * Apache Tika client interface
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.10/formats.html
 * @package Vaites\ApacheTika
 */
abstract class Client
{
    /**
     * Cached responses to avoid multiple request for the same file
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Get a class instance
     *
     * @param   string  $param  path or host
     * @param   mixed   $extra  currently, only port for web client
     * @return  \Vaites\ApacheTika\Client
     */
    public static function make($param = null, $extra = null)
    {
        if(preg_match('/\.jar$/', func_get_arg(0)))
        {
            return new CLIClient($param);
        }
        else
        {
            return new WebClient($param, $extra);
        }
    }

    /**
     * Gets file metadata
     *
     * @param   string  $file
     * @return  \Vaites\ApacheTika\Metadata\Metadata
     * @throws  \Exception
     */
    public function getMetadata($file)
    {
        return $this->request($file, 'meta');
    }

    /**
     * Detect language
     *
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function getLanguage($file)
    {
        return $this->request($file, 'lang');
    }

    /**
     * Detect MIME type
     *
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function getMIME($file)
    {
        return $this->request($file, 'mime');
    }

    /**
     * Extracts HTML
     *
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function getHTML($file)
    {
        return $this->request($file, 'html');
    }

    /**
     * Extracts text
     *
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function getText($file)
    {
        return $this->request($file, 'text');
    }

    /**
     * Returns current Tika version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->request(null, 'version');
    }

    /**
     * Configure and make a request and return its results.
     *
     * @param string $file
     * @param string $type
     *
     * @return string
     *
     * @throws \Exception
     */
    abstract protected function request($file, $type);
}
