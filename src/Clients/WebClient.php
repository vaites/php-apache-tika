<?php

namespace Vaites\ApacheTika\Clients;

use Exception;

use Vaites\ApacheTika\Client;

/**
 * Apache Tika web client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://cwiki.apache.org/confluence/display/TIKA/TikaServer
 */
class WebClient extends Client
{
    const MODE = 'web';

    /**
     * Cached responses to avoid multiple request for the same file
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Apache Tika server host
     *
     * @var string
     */
    protected $host = null;

    /**
     * Apache Tika server port
     *
     * @var int
     */
    protected $port = null;

    /**
     * Number of retries on server error
     *
     * @var int
     */
    protected $retries = 3;

    /**
     * Default cURL options
     *
     * @var array
     */
    protected $options =
    [
        CURLINFO_HEADER_OUT    => true,
        CURLOPT_HTTPHEADER     => [],
        CURLOPT_PUT            => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5
    ];

    /**
     * Configure class and test if server is running
     *
     * @param   string  $host
     * @param   int     $port
     * @param   array   $options
     * @param   bool    $check
     * @throws  \Exception
     */
    public function __construct($host = null, $port = null, $options = [], $check = true)
    {
        parent::__construct();

        if(is_string($host) && filter_var($host, FILTER_VALIDATE_URL))
        {
            $this->setUrl($host);
        }
        elseif($host)
        {
            $this->setHost($host);
        }

        if(is_numeric($port))
        {
            $this->setPort($port);
        }

        if(!empty($options))
        {
            $this->setOptions($options);
        }

        $this->setDownloadRemote(true);

        if($check === true)
        {
            $this->check();
        }
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getUrl()
    {
        return sprintf('http://%s:%d', $this->host, $this->port ?: 9998);
    }

    /**
     * Set the host and port using an URL
     *
     * @param   string  $url
     * @return $this
     */
    public function setUrl($url)
    {
        $url = parse_url($url);

        $this->setHost($url['host']);

        if(isset($url['port']))
        {
            $this->setPort($url['port']);
        }

        return $this;
    }

    /**
     * Get the host
     *
     * @return  null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host
     *
     * @param   string  $host
     * @return  $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the port
     *
     * @return  null|int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the port
     *
     * @param   int     $port
     * @return  $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the number of retries
     *
     * @return  int
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * Set the number of retries
     *
     * @param   int     $retries
     * @return  $this
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * Get all the options
     *
     * @return  null|array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get an specified option
     *
     * @param   string  $key
     * @return  mixed
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    /**
     * Set a cURL option to be set with curl_setopt()
     *
     * @link    http://php.net/manual/en/curl.constants.php
     * @link    http://php.net/manual/en/function.curl-setopt.php
     * @param   string  $key
     * @param   mixed   $value
     * @return  $this
     * @throws  \Exception
     */
    public function setOption($key, $value)
    {
        if(in_array($key, [CURLINFO_HEADER_OUT, CURLOPT_PUT, CURLOPT_RETURNTRANSFER]))
        {
            throw new Exception("Value for cURL option $key cannot be modified", 3);
        }

        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Set the cURL options
     *
     * @param   array   $options
     * @return  $this
     * @throws  \Exception
     */
    public function setOptions($options)
    {
        foreach($options as $key => $value)
        {
            $this->setOption($key, $value);
        }

        return $this;
    }

    /**
     * Get the timeout value for cURL
     *
     * @return  int
     */
    public function getTimeout()
    {
        return $this->getOption(CURLOPT_TIMEOUT);
    }

    /**
     * Set the timeout value for cURL
     *
     * @param   int     $value
     * @return  $this
     * @throws  \Exception
     */
    public function setTimeout($value)
    {
        $this->setOption(CURLOPT_TIMEOUT, (int) $value);

        return $this;
    }

    /**
     * Check if server is running
     *
     * @throws \Exception
     */
    public function check()
    {
        if($this->isChecked() === false)
        {
            $this->setChecked(true);

            // throws an exception if server is unreachable or can't connect
            $this->request('version');
        }
    }

    /**
     * Configure, make a request and return its results
     *
     * @param   string  $type
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function request($type, $file = null)
    {
        static $retries = [];

        // check if not checked
        $this->check();

        // check if is cached
        if($this->isCached($type, $file))
        {
            return $this->getCachedResponse($type, $file);
        }
        elseif(!isset($retries[sha1($file)]))
        {
            $retries[sha1($file)] = $this->retries;
        }

        // parameters for cURL request
        list($resource, $headers) = $this->getParameters($type, $file);

        // check the request
        $file = $this->checkRequest($type, $file);

        // cURL options
        $options = $this->getCurlOptions($type, $file);

        // sets headers
        foreach($headers as $header)
        {
            $options[CURLOPT_HTTPHEADER][] = $header;
        }

        // cURL init and options
        $options[CURLOPT_URL] = $this->getUrl() . "/$resource";

        // get the response and the HTTP status code
        list($response, $status) = $this->exec($options);

        // reduce memory usage closing cURL resource
        if(isset($options[CURLOPT_INFILE]) && is_resource($options[CURLOPT_INFILE]))
        {
            fclose($options[CURLOPT_INFILE]);
        }

        // request completed successfully
        if($status == 200)
        {
            // cache certain responses
            if($this->isCacheable($type))
            {
                $this->cacheResponse($type, $response, $file);
            }
        }
        // request completed successfully but result is empty
        elseif($status == 204)
        {
            $response = null;
        }
        // retry on request failed with error 500
        elseif($status == 500 && $retries[sha1($file)]--)
        {
            $response = $this->request($type, $file);
        }
        // other status code is an error
        else
        {
            $this->error($status, $resource, $file);
        }

        return $response;
    }

    /**
     * Make a request to Apache Tika Server
     *
     * @param   array   $options
     * @return  array
     * @throws  \Exception
     */
    protected function exec(array $options = [])
    {
        // cURL init and options
        $curl = curl_init();

        // add options only if cURL init doesn't fails
        if(is_resource($curl))
        {
            // we avoid curl_setopt_array($curl, $options) because strange Windows behaviour (issue #8)
            foreach($options as $option => $value)
            {
                curl_setopt($curl, $option, $value);
            }

            // make the request directly
            if(is_null($this->callback))
            {
                $this->response = curl_exec($curl) ?: '';
            }
            // with a callback, the response is appended on each block inside the callback
            else
            {
                $this->response = '';
                curl_exec($curl);
            }
        }

        // exception if cURL fails
        if($curl === false)
        {
            throw new Exception('Unexpected error');
        }
        elseif(curl_errno($curl))
        {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        // return the response and the status code
        return [trim($this->response), curl_getinfo($curl, CURLINFO_HTTP_CODE)];
    }

    /**
     * Throws an exception for an error status code
     *
     * @codeCoverageIgnore
     *
     * @param   int       $status
     * @param   string    $resource
     * @param   string    $file
     * @throws  \Exception
     */
    protected function error($status, $resource, $file = null)
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
                $message = 'Unprocessable document';

                // using remote files require Tika server to be launched with specific options
                if($this->downloadRemote == false && preg_match('/^http/', $file))
                {
                    $message .= ' (is server launched using "-enableUnsecureFeatures -enableFileUrl" arguments?)';
                }

                throw new Exception($message, 422);
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

    /**
     * Get the parameters to make the request
     *
     * @link    https://wiki.apache.org/tika/TikaJAXRS#Specifying_a_URL_Instead_of_Putting_Bytes
     * @param   string  $type
     * @param   string  $file
     * @return  array
     * @throws  \Exception
     */
    protected function getParameters($type, $file = null)
    {
        $headers = [];

        if(!empty($file) && preg_match('/^http/', $file))
        {
            $headers[] = "fileUrl:$file";
        }

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
            case 'rmeta/html':
            case 'rmeta/ignore':
            case 'rmeta/text':
                $resource = $type;
                $headers[] = 'Accept: application/json';
                break;

            case 'text':
                $resource = 'tika';
                $headers[] = 'Accept: text/plain';
                break;

            case 'text-main':
                $resource = 'tika/main';
                $headers[] = 'Accept: text/plain';
                break;

            case 'detectors':
            case 'parsers':
            case 'mime-types':
            case 'version':
                $resource = $type;
                break;

            default:
                throw new Exception("Unknown type $type");
        }

        return [$resource, $headers];
    }

    /**
     * Get the cURL options
     *
     * @param   string  $type
     * @param   string  $file
     * @return  array
     * @throws  \Exception
     */
    protected function getCurlOptions($type, $file = null)
    {
        // base options
        $options = $this->options;

        // callback
        if(!is_null($this->callback))
        {
            $callback = $this->callback;

            $options[CURLOPT_WRITEFUNCTION] = function($handler, $data) use($callback)
            {
                if($this->callbackAppend === true)
                {
                    $this->response .= $data;
                }

                $callback($data);

                // safe because cURL must receive the number of *bytes* written
                return strlen($data);
            };
        }

        // remote file options
        if($file && preg_match('/^http/', $file))
        {
            //
        }
        // local file options
        elseif($file && file_exists($file) && is_readable($file))
        {
            $options[CURLOPT_INFILE] = fopen($file, 'r');
            $options[CURLOPT_INFILESIZE] = filesize($file);
        }
        // other options for specific requests
        elseif(in_array($type, ['detectors', 'mime-types', 'parsers', 'version']))
        {
            $options[CURLOPT_PUT] = false;
        }
        // file not accesible
        else
        {
            throw new Exception("File $file can't be opened");
        }

        return $options;
    }
}
