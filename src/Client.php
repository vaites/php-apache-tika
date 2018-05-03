<?php

namespace Vaites\ApacheTika;

use Closure;
use Exception;

use Vaites\ApacheTika\Clients\CLIClient;
use Vaites\ApacheTika\Clients\WebClient;

/**
 * Apache Tika client interface
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.10/formats.html
 */
abstract class Client
{
    /**
     * List of supported Apache Tika versions
     *
     * @var array
     */
    protected static $supportedVersions =
    [
        '1.7', '1.8', '1.9', '1.10', '1.11', '1.12', '1.13', '1.14', '1.15', '1.16', '1.17', '1.18'
    ];

    /**
     * Response using callbacks
     *
     * @var string
     */
    protected $response = null;

    /**
     * Cached responses to avoid multiple request for the same file.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Callback called on secuential read
     *
     * @var \Closure
     */
    protected $callback = null;

    /**
     * Size of chunks for callback
     *
     * @var int
     */
    protected $chunkSize = 1048576;

    /**
     * Get a class instance
     *
     * @param   string  $param1     path or host
     * @param   int     $param2     Java binary path or port for web client
     * @param   array   $options    options for cURL request
     * @return  \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
     * @throws  \Exception
     */
    public static function make($param1 = null, $param2 = null, $options = [])
    {
        if (preg_match('/\.jar$/', func_get_arg(0)))
        {
            return new CLIClient($param1, $param2);
        }
        else
        {
            return new WebClient($param1, $param2, $options);
        }
    }

    /**
     * Get the callback
     *
     * @return  \Closure|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set the callback (callable or closure) for call on secuential read
     *
     * @param   mixed   $callback
     * @return  $this
     * @throws  \Exception
     */
    public function setCallback($callback)
    {
        if($callback instanceof Closure)
        {
            $this->callback = $callback;
        }
        elseif(is_callable($callback))
        {
            $this->callback = function($chunk) use($callback)
            {
                return call_user_func_array($callback, [$chunk]);
            };
        }
        else
        {
            throw new Exception('Invalid callback');
        }

        return $this;
    }

    /**
     * Get the chunk size
     *
     * @return  int
     */
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * Set the chunk size for secuential read
     *
     * @param   int     $size
     * @return  $this
     * @throws  \Exception
     */
    public function setChunkSize($size)
    {
        if(static::MODE == 'cli' && is_numeric($size))
        {
            $this->chunkSize = (int)$size;
        }
        elseif(static::MODE == 'web')
        {
            throw new Exception('Chunk size is not supported on web mode');
        }
        else
        {
            throw new Exception("$size is not a valid chunk size");
        }

        return $this;
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
        return $this->request('meta', $file);
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
        return $this->request('lang', $file);
    }

    /**
     * Detect MIME type
     *
     * @param   string  $file
     * @return  string
     * @throws \Exception
     */
    public function getMIME($file)
    {
        return $this->request('mime', $file);
    }

    /**
     * Extracts HTML
     *
     * @param   string  $file
     * @param   mixed   $callback
     * @return  string
     * @throws  \Exception
     */
    public function getHTML($file, $callback = null)
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback);
        }

        return $this->request('html', $file);
    }

    /**
     * Extracts text
     *
     * @param   string  $file
     * @param   mixed   $callback
     * @return  string
     * @throws  \Exception
     */
    public function getText($file, $callback = null)
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback);
        }

        return $this->request('text', $file);
    }

    /**
     * Extracts main text
     *
     * @param   string  $file
     * @param   mixed   $callback
     * @return  string
     * @throws  \Exception
     */
    public function getMainText($file, $callback = null)
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback);
        }

        return $this->request('text-main', $file);
    }

    /**
     * Returns current Tika version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->request('version');
    }

    /**
     * Return the list of Apache Tika supported versions
     *
     * @return array
     */
    public static function getSupportedVersions()
    {
        return self::$supportedVersions;
    }

    /**
     * Check the request before executing
     *
     * @param   string  $type
     * @param   string  $file
     * @throws  \Exception
     */
    public function checkRequest($type, $file)
    {
        // invalid local file
        if($file && !preg_match('/^http/', $file) && !file_exists($file))
        {
            throw new Exception("File $file can't be opened");
        }
        // invalid remote file
        elseif($file && preg_match('/^http/', $file) && !preg_match('/200/', get_headers($file)[0]))
        {
            throw new Exception("File $file can't be opened", 2);
        }
    }

    /**
     * Configure and make a request and return its results.
     *
     * @param   string  $type
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    abstract public function request($type, $file);
}
