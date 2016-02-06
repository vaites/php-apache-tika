<?php

namespace Vaites\ApacheTika;

use Vaites\ApacheTika\Clients\CLIClient;
use Vaites\ApacheTika\Clients\WebClient;

/**
 * Apache Tika client interface.
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 *
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.10/formats.html
 */
abstract class Client
{
    /**
     * Cached responses to avoid multiple request for the same file.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Get a class instance.
     *
     * @param string $param   path or host
     * @param int    $port    only port for web client
     * @param array  $options options for cURL request
     *
     * @return \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
     */
    public static function make($param = null, $port = null, $options = [])
    {
        if(preg_match('/\.jar$/', func_get_arg(0)))
        {
            return new CLIClient($param);
        }
        else
        {
            return new WebClient($param, $port, $options);
        }
    }

    /**
     * Gets file metadata.
     *
     * @param string $file
     *
     * @return \Vaites\ApacheTika\Metadata\Metadata
     *
     * @throws \Exception
     */
    public function getMetadata($file)
    {
        return $this->request('meta', $file);
    }

    /**
     * Detect language.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getLanguage($file)
    {
        return $this->request('lang', $file);
    }

    /**
     * Detect MIME type.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getMIME($file)
    {
        return $this->request('mime', $file);
    }

    /**
     * Extracts HTML.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getHTML($file)
    {
        return $this->request('html', $file);
    }

    /**
     * Extracts text.
     *
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getText($file)
    {
        return $this->request('text', $file);
    }

    /**
     * Returns current Tika version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->request('version');
    }

    /**
     * Configure and make a request and return its results.
     *
     * @param string $type
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    abstract public function request($type, $file);
}
