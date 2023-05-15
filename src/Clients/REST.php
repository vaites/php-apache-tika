<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Clients;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Exceptions\Exception;

/**
 * Apache Tika rest client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://cwiki.apache.org/confluence/display/TIKA/TikaServer
 */
class REST extends Client
{
    /**
     * Apache Tika server URL
     */
    protected string $url = 'http://localhost:9998';

    /**
     * Number of retries on server error
     */
    protected int $retries = 3;

    /**
     * List of OCR languages
     */
    protected array $ocrLanguages;

    /**
     * Default cURL options
     */
    protected array $options =
    [
        CURLINFO_HEADER_OUT     => true,
        CURLOPT_HTTPHEADER      => [],
        CURLOPT_PUT             => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 5
    ];

    /**
     * Configure class and test if server is running
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function __construct(string $url = null, array $options = null, bool $check = null)
    {
        parent::__construct();

        if($url === null && getenv('APACHE_TIKA_URL') !== false)
        {
            $url = getenv('APACHE_TIKA_URL');
        }

        if($url !== null)
        {
            $this->setUrl($url);
        }

        if(is_array($options))
        {
            $this->setOptions($options);
        }

        if($check === true)
        {
            $this->check();
        }
    }

    /**
     * Get the base URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the base URL
     */
    public function setUrl(string $url): self
    {
        $this->url = str_contains($url, '://') ? $url : "http://$url:9998";

        return $this;
    }

    /**
     * Get the number of retries
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * Set the number of retries
     */
    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * Get all the options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get an specified option
     *
     * @return  mixed
     */
    public function getOption(int $key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Set a cURL option to be set with curl_setopt()
     *
     * @link http://php.net/manual/en/curl.constants.php
     * @link http://php.net/manual/en/function.curl-setopt.php
     * @param mixed $value
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setOption(int $key, $value): self
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
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setOptions(array $options): self
    {
        foreach($options as $key => $value)
        {
            $this->setOption($key, $value);
        }

        return $this;
    }

    /**
     * Get all the HTTP headers
     */
    public function getHeaders(): array
    {
        return $this->options[CURLOPT_HTTPHEADER];
    }

    /**
     * Get the specified HTTP header
     */
    public function getHeader(string $name): ?string
    {
        $value = null;

        foreach($this->options[CURLOPT_HTTPHEADER] as $header)
        {
            if(preg_match("/$name:\s+(.+)/i", $header, $match))
            {
                $value = $match[1];
                break;
            }
        }

        return $value;
    }

    /**
     * Set a cURL header to be set with curl_setopt()
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setHeader(string $name, string $value): self
    {
        $this->options[CURLOPT_HTTPHEADER][] = "$name: $value";

        return $this;
    }

    /**
     * Set the HTTP headers
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setHeaders(array $headers): self
    {
        foreach($headers as $name => $value)
        {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Get the accepted OCR languages
     */
    public function getOCRLanguages(): array
    {
        return $this->ocrLanguages ?? [];
    }

    /**
     * Set the accepted OCR language
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setOCRLanguage(string $language): self
    {  
        return $this->setOCRLanguages([$language]);
    }

    /**
     * Set the accepted OCR languages
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setOCRLanguages(array $languages): self
    {
        $this->ocrLanguages = $languages;
        $this->setHeader('X-Tika-OCRLanguage', implode('+', $languages));

        return $this;
    }

    /**
     * Get the timeout value for cURL
     */
    public function getTimeout(): int
    {
        $timeout = $this->getOption(CURLOPT_TIMEOUT);

        return is_numeric($timeout) ? (int) $timeout : 0;
    }

    /**
     * Set the timeout value for cURL
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setTimeout(int $value): self
    {
        $this->setOption(CURLOPT_TIMEOUT, (int) $value);

        return $this;
    }

    /**
     * Returns the supported MIME types
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getSupportedMIMETypes(): array
    {
        $mimeTypes = json_decode($this->request('mime-types'), true);

        if(is_array($mimeTypes))
        {
            ksort($mimeTypes);
        }
        else
        {
            $mimeTypes = [];
        }

        return $mimeTypes;
    }

    /**
     * Returns the available detectors
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getAvailableDetectors(): array
    {
        $response = json_decode($this->request('detectors'), true);

        if(is_array($response))
        {
            $detectors = [$response];

            foreach($detectors as $index => $parent)
            {
                $detectors[$parent['name']] = $parent;

                if(isset($parent['children']))
                {
                    foreach($parent['children'] as $subindex => $child)
                    {
                        $detectors[$parent['name']]['children'][$child['name']] = $child;

                        unset($detectors[$parent['name']]['children'][$subindex]);
                    }
                }

                unset($detectors[$index]);
            }
        }
        else
        {
            $detectors = [];
        }

        return $detectors;
    }

    /**
     * Returns the available parsers
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getAvailableParsers(): array
    {
        $response = json_decode($this->request('parsers'), true);

        if(is_array($response))
        {
            $parsers = [$response];
        
            foreach($parsers as $index => $parent)
            {
                $parsers[$parent['name']] = $parent;

                if(isset($parent['children']))
                {
                    foreach($parent['children'] as $subindex => $child)
                    {
                        $parsers[$parent['name']]['children'][$child['name']] = $child;

                        unset($parsers[$parent['name']]['children'][$subindex]);
                    }
                }

                unset($parsers[$index]);
            }
        }
        else
        {
            $parsers = [];
        }

        return $parsers;
    }

    /**
     * Check server connection
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function check(): void
    {
        if($this->isChecked() === false)
        {
            // throws an exception if server is unreachable or can't connect
            $this->setVersion($this->request('version'))->setChecked(true);
        }
    }

    /**
     * Configure, make a request and return its results
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function request(string $type, string $file = null): string
    {
        static $retries = [];

        // check if not checked
        if($type !== 'version')
        {
            $this->check();
        }

        // check if is cached
        if($file !== null && $this->isCached($type, $file))
        {
            return (string) $this->getCachedResponse($type, $file);
        }
        elseif($file !== null && !isset($retries[sha1($file)]))
        {
            $retries[sha1($file)] = $this->retries;
        }

        // parameters for cURL request
        [$resource, $headers] = $this->getParameters($type, $file);

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
        [$response, $status] = $this->exec($options);

        // reduce memory usage closing cURL resource
        if(isset($options[CURLOPT_INFILE]) && is_resource($options[CURLOPT_INFILE]))
        {
            fclose($options[CURLOPT_INFILE]);
        }

        // request completed successfully
        if($status == 200)
        {
            // cache certain responses
            if($file !== null && $this->isCacheable($type))
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
        elseif($status == 500 && $file !== null && $retries[sha1($file)]--)
        {
            $response = $this->request($type, $file);
        }
        // other status code is an error
        else
        {
            $this->error($status, $resource, $file);
        }

        return $this->filterResponse($response);
    }

    /**
     * Make a request to Apache Tika Server
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function exec(array $options = []): array
    {
        // cURL init and options
        $curl = curl_init();

        // we avoid curl_setopt_array($curl, $options) because strange Windows behaviour (issue #8)
        foreach($options as $option => $value)
        {
            curl_setopt($curl, $option, $value);
        }

        // make the request directly
        if(!isset($this->callback))
        {
            $this->response = (string) curl_exec($curl);
        }
        // with a callback, the response is appended on each block inside the callback
        else
        {
            $this->response = '';
            curl_exec($curl);
        }

        // exception if cURL fails
        if(curl_errno($curl))
        {
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        // return the response and the status code
        return [trim($this->response), curl_getinfo($curl, CURLINFO_HTTP_CODE)];
    }

    /**
     * Throws an exception for an error status code
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function error(int $status, string $resource, string $file = null): void
    {
        switch($status)
        {
            //  method not allowed
            case 405:
                $message = 'Method not allowed';
                break;

            //  unsupported media type
            case 415:
                $message = 'Unsupported media type';
                break;

            //  unprocessable entity
            case 422:
                $message = 'Unprocessable document';

                // using remote files require Tika server to be launched with specific options
                if($this->downloadRemote === false && $file !== null && preg_match('/^http/', $file))
                {
                    $message .= ' (is server launched using "-enableUnsecureFeatures -enableFileUrl" arguments?)';
                }
                // using custom OCR languages require Tesseract's language files too
                elseif(isset($this->ocrLanguages))
                {
                    $message .= sprintf(' (have Tesseract trained data for %s?)', implode(', ', $this->ocrLanguages));
                }

                break;

            // server error
            case 500:
                $message = 'Error while processing document';
                break;

            // unexpected
            default:
                $message = "Unexpected response for /$resource ($status)";
                $status = 501;
        }

        throw new Exception($message, $status);
    }

    /**
     * Get the parameters to make the request
     *
     * @link https://wiki.apache.org/tika/TikaJAXRS#Specifying_a_URL_Instead_of_Putting_Bytes
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function getParameters(string $type, string $file = null): array
    {
        $headers = [];
        $callback = null;

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
                $resource = 'detect/stream';

                if($file !== null)
                {
                    $name = basename($file);
                    $headers[] = "Content-Disposition: attachment, filename=$name";
                }
                break;

            case 'detectors':
            case 'parsers':
            case 'meta':
            case 'mime-types':
            case 'rmeta/html':
            case 'rmeta/ignore':
            case 'rmeta/text':
                $resource = $type;
                $headers[] = 'Accept: application/json';
                $callback = fn($response) => json_decode($response, true);
                break;

            case 'text':
                $resource = 'tika';
                $headers[] = 'Accept: text/plain';
                break;

            case 'text-main':
                $resource = 'tika/main';
                $headers[] = 'Accept: text/plain';
                break;

            case 'version':
                $resource = $type;
                break;

            case 'xhtml':
                throw new Exception("Tika Server does not support XHTML output");

            default:
                throw new Exception("Unknown type $type");
        }

        return [$resource, $headers, $callback];
    }

    /**
     * Get the cURL options
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function getCurlOptions(string $type, string $file = null): array
    {
        // base options
        $options = $this->options;

        // callback
        if(isset($this->callback))
        {
            $callback = $this->callback;

            $options[CURLOPT_WRITEFUNCTION] = function($handler, $data) use ($callback)
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
