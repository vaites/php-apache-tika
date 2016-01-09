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
 * @link    https://tika.apache.org/1.11/formats.html
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
     * Is server running?
     *
     * @param string $host
     * @param int    $port
     *
     * @throws Exception
     */
    public function __construct($host = null, $port = null)
    {
        if ($host) {
            $this->host = $host;
        }

        if ($port) {
            $this->port = $port;
        }

        $this->exec([
            CURLOPT_TIMEOUT => 1,
            CURLOPT_URL => "http://{$this->host}:{$this->port}/tika",
        ]);
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
    protected function request($file, $type)
    {
        // check if is cached
        if (isset($this->cache[sha1($file)][$type])) {
            return $this->cache[sha1($file)][$type];
        }

        // parameters for cURL request
        $headers = [];
        switch ($type) {
            case 'html':
                $resource = 'tika';
                $headers[] = 'Accept: text/html';
                break;

            case 'mime':
                $name = basename($file);
                $resource = 'detect/stream';
                $headers[] = "Content-Disposition: attachment, filename=$name";
                break;

            case 'lang':
                $resource = 'language/stream';
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

        // cURL base options
        $options = [CURLOPT_PUT => true];

        // remote file options
        if ($file && preg_match('/^http/', $file)) {
            $options[CURLOPT_INFILE] = fopen($file, 'r');
        }
        // local file options
        elseif ($file && file_exists($file) && is_readable($file)) {
            $options[CURLOPT_INFILE] = fopen($file, 'r');
            $options[CURLOPT_INFILESIZE] = filesize($file);
        } elseif ($type == 'version') {
            $options = [CURLOPT_PUT => false];
        }
        // error
        else {
            throw new Exception("File $file can't be opened");
        }

        // sets headers
        $options[CURLOPT_HTTPHEADER] = $headers;

        // cURL init and options
        $options[CURLOPT_URL] = "http://{$this->host}:{$this->port}"."/$resource";

        // get the response and the HTTP status code
        list($response, $status) = $this->exec($options);

        switch ($status) {
            // request completed successfully
            case 200:
                if ($type == 'meta') {
                    $response = Metadata::make($response, $file);
                }
                break;

            // request completed sucessfully but result is empty
            case 204:
                $response = null;
                break;

            //  unsupported media type
            case 415:
                throw new Exception('Unsupported media type');
                break;

            //  unprocessable entity
            case 422:
                throw new Exception('Unprocessable document');
                break;

            // server error
            case 500:
                throw new Exception('Error while processing document');
                break;

            // unexpected
            default:
                throw new Exception("Unexpected response for /$resource ($status)");
        }

        // cache certain responses
        if (in_array($type, ['lang', 'meta'])) {
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
        curl_setopt_array($curl,
        [
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ] + $options);

        // get the response and the HTTP status code
        $response =
        [
            trim(curl_exec($curl)),
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
        ];

        // exception if cURL fails
        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        return $response;
    }
}
