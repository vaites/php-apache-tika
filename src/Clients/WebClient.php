<?php

namespace Vaites\ApacheTika\Clients;

use Exception;
use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Metadata\Metadata;

/**
 * Apache Tika web client.
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 *
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.12/formats.html
 */
class WebClient extends Client
{
    /**
     * Cached responses to avoid multiple request for the same file.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Apache Tika server host.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * Apache Tika server port.
     *
     * @var int
     */
    protected $port = 9998;

    /**
     * cURL options.
     *
     * @var array
     */
    protected $options =
    [
        CURLINFO_HEADER_OUT    => true,
        CURLOPT_PUT            => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ];

    /**
     * Configure class and test if server is running
     *
     * @param string $host
     * @param int    $port
     *
     * @throws Exception
     */
    public function __construct($host = null, $port = null, $options = [])
    {
        if($host)
        {
            $this->host = $host;
        }

        if($port)
        {
            $this->port = $port;
        }

        foreach($options as $key => $value)
        {
            $this->options[$key] = $value;
        }

        $this->getVersion(); // exception if not running
    }

    /**
     * Get the host.
     *
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host.
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Get the port.
     *
     * @return null|int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the port.
     *
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Get the options.
     *
     * @return null|array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the options.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        foreach($options as $key => $value)
        {
            $this->options[$key] = $value;
        }
    }

    /**
     * Configure, make a request and return its results.
     *
     * @param string $type
     * @param string $file
     *
     * @return string
     *
     * @throws \Exception
     */
    public function request($type, $file = null)
    {
        // check if is cached
        if(isset($this->cache[sha1($file)][$type]))
        {
            return $this->cache[sha1($file)][$type];
        }

        // parameters for cURL request
        $headers = [];
        switch($type)
        {
            case 'html':
                $resource = 'tika';
                $headers[] = 'Accept: text/html';
                break;

            case 'lang':
                $resource = 'language/stream';
                break;

            case 'mime':
                $name = basename($file);
                $resource = 'detect/stream';
                $headers[] = "Content-Disposition: attachment, filename=$name";
                break;

            case 'meta':
                $resource = 'meta';
                $headers[] = 'Accept: application/json';
                break;

            case 'text':
                $resource = 'tika';
                $headers[] = 'Accept: text/plain';
                break;

            case 'version':
                $resource = 'version';
                break;

            default:
                throw new Exception("Unknown type $type");
        }

        // base options
        $options = $this->options;

        // remote file options
        if($file && preg_match('/^http/', $file))
        {
            $options[CURLOPT_INFILE] = fopen($file, 'r');
        }
        // local file options
        elseif($file && file_exists($file) && is_readable($file))
        {
            $options[CURLOPT_INFILE] = fopen($file, 'r');
            $options[CURLOPT_INFILESIZE] = filesize($file);
        }
        elseif($type == 'version')
        {
            $options[CURLOPT_PUT] = false;
        }
        // error
        else
        {
            throw new Exception("File $file can't be opened");
        }

        // sets headers
        $options[CURLOPT_HTTPHEADER] = $headers;

        // cURL init and options
        $options[CURLOPT_URL] = "http://{$this->host}:{$this->port}" . "/$resource";

        // get the response and the HTTP status code
        list($response, $status) = $this->exec($options);

        // request completed successfully
        if($status == 200)
        {
            if($type == 'meta')
            {
                $response = Metadata::make($response, $file);
            }
        }
        // request completed successfully but result is empty
        elseif($status == 204)
        {
            $response = null;
        }
        // other status code is an error
        else
        {
            $this->error($status, $resource);
        }

        // cache certain responses
        if(in_array($type, ['lang', 'meta']))
        {
            $this->cache[sha1($file)][$type] = $response;
        }

        return $response;
    }

    /**
     * Make a request to Apache Tika Server.
     *
     * @param array $options
     *
     * @return array
     *
     * @throws Exception
     */
    protected function exec(array $options = [])
    {
        // cURL init and options
        $curl = curl_init();
        curl_setopt_array($curl, $options);

        // get the response and the HTTP status code
        $response =
        [
            trim(curl_exec($curl)),
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
        ];

        // exception if cURL fails
        if(curl_errno($curl))
        {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        return $response;
    }

    /**
     * Throws an exception for an error status code
     *
     * @codeCoverageIgnore
     *
     * @param int       $status
     * @param string    $resource
     *
     * @throws Exception
     */
    protected function error($status, $resource)
    {
        switch($status)
        {
            //  method not allowed
            case 405:
                throw new Exception('Method not allowed', 405);
                break;

            //  unsupported media type
            case 415:
                throw new Exception('Unsupported media type', 415);
                break;

            //  unprocessable entity
            case 422:
                throw new Exception('Unprocessable document', 422);
                break;

            // server error
            case 500:
                throw new Exception('Error while processing document', 500);
                break;

            // unexpected
            default:
                throw new Exception("Unexpected response for /$resource ($status)", 501);
        }
    }
}
