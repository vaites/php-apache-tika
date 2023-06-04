<?php declare(strict_types=1);

namespace Vaites\ApacheTika;

use Closure;
use stdClass;

use Vaites\ApacheTika\Clients\CLI;
use Vaites\ApacheTika\Clients\REST;
use Vaites\ApacheTika\Contracts\Client as ClientContract;
use Vaites\ApacheTika\Contracts\Metadata as MetadataContract;
use Vaites\ApacheTika\Exceptions\Exception;
/**
 * Apache Tika client interface
 *
 * @link    https://tika.apache.org/2.7.0/formats.html
 */
abstract class Client implements ClientContract
{
    /**
     * Platform (unix or win)
     */
    protected string $platform;

    /**
     * Apache Tika version
     */
    protected string $version;

    /**
     * Text encoding
     */
    protected string $encoding;

    /**
     * Request response
     */
    protected string $response;

    /**
     * Checked flag
     */
    protected bool $checked = false;

    /**
     * Callback called on sequential read
     */
    protected Closure $callback;

    /**
     * Enable or disable appending when using callback
     */
    protected bool $callbackAppend = true;

    /**
     * Size of chunks for read callback
     */
    protected int $chunkSize = 1048576;

    /**
     * Remote download flag
     */
    protected bool $downloadRemote = true;

    /**
     * Allow unsupported versions
     */
    protected bool $unsupportedVersions = false;

    /**
     * Timezone
     */
    protected string $timezone = 'UTC';

    /**
     * Cached responses to avoid multiple request for the same file.
     */
    protected array $cache = [];
    
    /**
     * Supported version list
     */
    protected static array $supportedVersions = 
    [
        '1.19', '1.19.1', '1.20', '1.21', '1.22', '1.23', '1.24', '1.24.1', '1.25',
        '1.26', '1.27', '1.28', '1.28.1', '1.28.2', '1.28.3', '1.28.4', '1.28.5',
        '2.0.0', '2.1.0', '2.2.0', '2.2.1', '2.3.0', '2.4.0', '2.5.0', '2.6.0', '2.7.0', '2.8.0'
    ];

    /**
     * Configure client
     */
    public function __construct()
    {
        $version = getenv('APACHE_TIKA_VERSION');

        if($version !== false)
        {
            $this->setVersion($version);
        }

        $this->platform = defined('PHP_WINDOWS_VERSION_MAJOR') ? 'win' : 'unix';
    }

    /**
     * Get a class instance throwing an exception if check fails
     */
    public static function make(string $param1 = null, int|string $param2 = null, array $options = null, bool $check = null): CLI|REST
    {
        if($param1 === null && $param2 === null && getenv('APACHE_TIKA_PATH') !== false)
        {
            $param1 = getenv('APACHE_TIKA_PATH');
        }
        elseif($param1 === null && $param2 === null && getenv('APACHE_TIKA_URL') !== false)
        {
            $param1 = getenv('APACHE_TIKA_URL');
        }

        if($param1 !== null && preg_match('/\.jar$/', $param1))
        {
            $client = new CLI($param1, (string) $param2, $check);

            if($options !== null)
            {
                $client->setJavaArgs($options);
            }
        }
        else
        {
            $client = new REST($param2 ? "$param1:$param2" : $param1, $options, $check);
        }

        return $client;
    }

    /**
     * Get a class instance delaying the check
     */
    public static function prepare(string $param1 = null, int|string $param2 = null, array $options = []): CLI|REST
    {
        return self::make($param1, $param2, $options, false);
    }

    /**
     * Get the encoding
     */
    public function getEncoding(): ?string
    {
        return $this->encoding ?? null;
    }

    /**
     * Set the encoding
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setEncoding(string $encoding): self
    {
        if(!empty($encoding))
        {
            $this->encoding = $encoding;
        }
        else
        {
            throw new Exception('Invalid encoding');
        }

        return $this;
    }

    /**
     * Get the callback
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * Set the callback (callable or closure) for call on secuential read
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function setCallback(callable $callback, bool $append = true): self
    {
        $this->callbackAppend = $append;

        if($callback instanceof Closure || is_array($callback))
        {
            $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
        }
        elseif(is_string($callback))
        {
            $this->callback = fn($chunk) => call_user_func_array($callback, [$chunk]);
        }
        else
        {
            throw new Exception('Invalid callback');
        }

        return $this;
    }

    /**
     * Get the chunk size
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Set the chunk size for secuential read
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;

        return $this;
    }

    /**
     * Get the timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Set the timezone
     */
    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get the remote download flag
     */
    public function getDownloadRemote(): bool
    {
        return $this->downloadRemote;
    }

    /**
     * Set the remote download flag
     */
    public function setDownloadRemote(bool $download): self
    {
        $this->downloadRemote = $download;

        return $this;
    }

    /**
     * Gets file metadata
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getMetadata(string $file, bool $content = false): MetadataContract
    {
        $response = $this->parseJsonResponse($this->request('meta', $file) ?: 'ERROR');

        if($response instanceof stdClass === false)
        {
            throw new Exception("Unexpected metadata response for $file");
        }

        $metadata = Metadata::make($response, $file, $this->getTimezone());

        if($content === true)
        {
            $metadata->content = $this->getText($file);
        }

        return $metadata;
    }

    /**
     * Gets recursive file metadata where the returned array indexes are the file name.
     *
     * Example: for a sample.zip with an example.doc file, the return array looks like if be defined as:
     *
     *  [
     *      'sample.zip' => new Metadata()
     *      'sample.zip/example.doc' => new Metadata\Document()
     *  ]
     *
     * @link https://cwiki.apache.org/confluence/display/TIKA/TikaServer#TikaServer-RecursiveMetadataandContent
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getRecursiveMetadata(string $file, ?string $format = 'ignore'): array
    {
        if(in_array($format, ['text', 'html', 'ignore']) === false)
        {
            throw new Exception("Unknown recursive type (must be text, html, ignore or null)");
        }

        $response = $this->parseJsonResponse($this->request("rmeta/$format", $file) ?: 'ERROR');

        if(is_array($response) === false)
        {
            throw new Exception("Unexpected metadata response for $file");
        }

        $metadata = [];

        foreach($response as $item)
        {
            $name = basename($file);
            if(isset($item->{'X-TIKA:embedded_resource_path'}))
            {
                $name .= $item->{'X-TIKA:embedded_resource_path'};
            }

            $metadata[$name] = Metadata::make($item, $file, $this->getTimezone());
        }

        return $metadata;
    }

    /**
     * Detect language
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getLanguage(string $file): ?string
    {
        return $this->request('lang', $file);
    }

    /**
     * Detect MIME type
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getMIME(string $file): ?string
    {
        return $this->request('mime', $file);
    }

    /**
     * Extracts HTML
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getHTML(string $file, callable $callback = null, bool $append = true): ?string
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback, $append);
        }

        return $this->request('html', $file);
    }

    /**
     * Extracts XHTML
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getXHTML(string $file, callable $callback = null, bool $append = true): ?string
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback, $append);
        }

        return $this->request('xhtml', $file);
    }

    /**
     * Extracts text
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getText(string $file, callable $callback = null, bool $append = true): ?string
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback, $append);
        }

        return $this->request('text', $file);
    }

    /**
     * Extracts main text
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getMainText(string $file, callable $callback = null, bool $append = true): ?string
    {
        if(!is_null($callback))
        {
            $this->setCallback($callback, $append);
        }

        return $this->request('text-main', $file);
    }

    /**
     * Returns current Tika version
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getVersion(bool $request = false): ?string
    {
        if($request === true || !isset($this->version))
        {
            $version = $this->request('version');
        
            if($version !== null)
            {
                $this->setVersion($version);
            }
        }

        return $this->version;
    }

    /**
     * Set the Tika version
     */
    public function setVersion(string $version): self
    {
        $version = trim(preg_replace('/Apache Tika/i', '', $version) ?: '');

        if(!in_array($version, $this->getSupportedVersions()) && $this->unsupportedVersions === false)
        {
            throw new Exception("Apache Tika $version is unsupported");
        }

        $this->version = $version;

        return $this;
    }

    /**
     * Return the list of Apache Tika supported versions
     */
    public function getSupportedVersions(): array
    {
        return self::$supportedVersions;
    }

    /**
     * Return the latest Apache Tika supported version
     */
    public function getLatestSupportedVersion(): string
    {
        return end(self::$supportedVersions);
    }

    /**
     * Allow usage with unsupported versions
     */
    public function allowUnsupportedVersions(): self
    {
        $this->unsupportedVersions = true;

        return $this;
    }

    /**
     * Disallow usage with unsupported versions
     */
    public function disallowUnsupportedVersions(): self
    {
        $this->unsupportedVersions = true;

        return $this;
    }

    /**
     * Check if unsupported versions are allowed
     */
    public function areUnsupportedVersionsAllowed(): bool
    {
        return $this->unsupportedVersions;
    }

    /**
     * Sets the checked flag
     */
    public function setChecked(bool $checked): self
    {
        $this->checked = (bool) $checked;

        return $this;
    }

    /**
     * Checks if instance is checked
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * Check if a response is cached
     */
    protected function isCached(string $type, string $file): bool
    {
        return isset($this->cache[sha1($file)][$type]);
    }

    /**
     * Get a cached response
     */
    protected function getCachedResponse(string $type, string $file): ?string
    {
        return $this->cache[sha1($file)][$type] ?? null;
    }

    /**
     * Check if a request type must be cached
     */
    protected function isCacheable(string $type): bool
    {
        return in_array($type, ['lang', 'meta']);
    }

    /**
     * Caches a response
     */
    protected function cacheResponse(string $type, string $response, string $file): bool
    {
        $this->cache[sha1($file)][$type] = $response;

        return true;
    }

    /**
     * Checks if a specific version is supported
     */
    public function isVersionSupported(string $version): bool
    {
        return in_array($version, $this->getSupportedVersions());
    }

    /**
     * Check if a mime type is supported
     *
     * @param string $mime
     * @return bool
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function isMIMETypeSupported(string $mime): bool
    {
        return array_key_exists($mime, $this->getSupportedMIMETypes());
    }

    /**
     * Check the request before executing
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function checkRequest(string $type, string $file = null): ?string
    {
        // no checks for getters
        if(in_array($type, ['detectors', 'mime-types', 'parsers', 'version']))
        {
            //
        } 
        // invalid local file
        elseif($file !== null && !preg_match('/^http/', $file) && !file_exists($file))
        {
            throw new Exception("File $file can't be opened");
        } 
        // invalid remote file
        elseif($file !== null && preg_match('/^http/', $file))
        {
            $headers = get_headers($file);

            // error if file can't be retrieved
            if(empty($headers) || !preg_match('/200/', $headers[0]))
            {
                throw new Exception("File $file can't be opened", 2);
            }
            // download remote file if required only for integrated downloader
            elseif($this->downloadRemote)
            {
                $file = $this->downloadFile($file);
            }
        } 

        return $file;
    }

    /**
     * Filter response to fix common issues
     *
     * @param string $response
     * @return string
     */
    protected function filterResponse(string $response): string
    {
        // fix Log4j2 warning
        $response = trim(str_replace
        (
            'WARNING: sun.reflect.Reflection.getCallerClass is not supported. This will impact performance.',
            '',
            $response
        ));

        return trim($response);
    }

    /**
     * Parse the response returned by Apache Tika
     *
     * @return mixed
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function parseJsonResponse(string $response)
    {
        // an empty response throws an error
        if(empty($response) || trim($response) == '')
        {
            throw new Exception('Empty response');
        }

        // decode the JSON response
        $json = json_decode($response);

        // exceptions if metadata is not valid
        if(json_last_error())
        {
            $message = function_exists('json_last_error_msg') ? json_last_error_msg() : 'Error parsing JSON response';

            throw new Exception($message, json_last_error());
        }

        return $json;
    }

    /**
     * Download file to a temporary folder and return its path
     *
     * @link https://wiki.apache.org/tika/TikaJAXRS#Specifying_a_URL_Instead_of_Putting_Bytes
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function downloadFile(string $file): string
    {
        $dest = tempnam(sys_get_temp_dir(), 'TIKA');

        if($dest === false)
        {
            throw new Exception("Can't create a temporary file at " . sys_get_temp_dir());
        }

        $fp = fopen($dest, 'w+');

        if($fp === false)
        {
            throw new Exception("$dest can't be opened");
        }

        $ch = curl_init($file);

        if($ch === false)
        {
            throw new Exception("$file can't be downloaded");
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);

        if(curl_errno($ch))
        {
            throw new Exception(curl_error($ch));
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if($code != 200)
        {
            throw new Exception("$file can't be downloaded", $code);
        }

        return $dest;
    }

    /**
     * Must return the supported MIME types
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    abstract public function getSupportedMIMETypes(): array;

    /**
     * Must return the available detectors
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    abstract public function getAvailableDetectors(): array;

    /**
     * Must return the available parsers
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    abstract public function getAvailableParsers(): array;

    /**
     * Check Java binary, JAR path or server connection
     */
    abstract protected function check(): void;

    /**
     * Configure and make a request and return its results.
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    abstract protected function request(string $type, string $file = null): ?string;
}
