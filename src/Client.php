<?php

namespace Vaites\ApacheTika;

use Closure;
use Exception;

use Vaites\ApacheTika\Clients\CLIClient;
use Vaites\ApacheTika\Clients\WebClient;
use Vaites\ApacheTika\Metadata\Metadata;

/**
 * Apache Tika client interface
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://tika.apache.org/1.23/formats.html
 */
abstract class Client
{
    protected const MODE = null;

    /**
     * List of supported Apache Tika versions
     *
     * @var array
     */
    protected static $supportedVersions =
    [
        '1.15', '1.16', '1.17', '1.18', '1.19', '1.19.1', '1.20', '1.21', '1.22', '1.23'
    ];

    /**
     * Checked flag
     *
     * @var bool
     */
    protected $checked = false;

    /**
     * Response using callbacks
     *
     * @var string
     */
    protected $response = null;

    /**
     * Platform (unix or win)
     *
     * @var string
     */
    protected $platform = null;

    /**
     * Cached responses to avoid multiple request for the same file.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Text encoding
     *
     * @var \Closure
     */
    protected $encoding = null;

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
     * Remote download flag
     *
     * @var bool
     */
    protected $downloadRemote = false;

    /**
     * Configure client
     */
    public function __construct()
    {
        $this->platform = defined('PHP_WINDOWS_VERSION_MAJOR') ? 'win' : 'unix';
    }

    /**
     * Get a class instance throwing an exception if check fails
     *
     * @param   string  $param1     path or host
     * @param   int     $param2     Java binary path or port for web client
     * @param   array   $options    options for cURL request
     * @param   bool    $check      check JAR file or server connection
     * @return  \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
     * @throws  \Exception
     */
    public static function make($param1 = null, $param2 = null, $options = [], $check = true): Client
    {
        if(preg_match('/\.jar$/', func_get_arg(0)))
        {
            return new CLIClient($param1, $param2, $check);
        }
        else
        {
            return new WebClient($param1, $param2, $options, $check);
        }
    }

    /**
     * Get a class instance delaying the check
     *
     * @param   string  $param1     path or host
     * @param   int     $param2     Java binary path or port for web client
     * @param   array   $options    options for cURL request
     * @return  \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
     * @throws  \Exception
     */
    public static function prepare($param1 = null, $param2 = null, $options = []): Client
    {
        return self::make($param1, $param2, $options, false);
    }

    /**
     * Get the encoding
     *
     * @return  \Closure|null
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set the encoding
     *
     * @param   string   $encoding
     * @return  $this
     * @throws  \Exception
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get the callback
     *
     * @return  \Closure|null
     */
    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    /**
     * Set the callback (callable or closure) for call on secuential read
     *
     * @param   mixed   $callback
     * @param   bool    $append
     * @return  $this
     * @throws  \Exception
     */
    public function setCallback($callback): self
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
    public function getChunkSize(): int
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
    public function setChunkSize(int $size): self
    {
        if(static::MODE == 'cli')
        {
            $this->chunkSize = $size;
        }
        else
        {
            throw new Exception('Chunk size is not supported on web mode');
        }

        return $this;
    }

    /**
     * Get the remote download flag
     *
     * @return  bool
     */
    public function getDownloadRemote(): bool
    {
        return $this->downloadRemote;
    }

    /**
     * Set the remote download flag
     *
     * @param   bool    $download
     * @return  $this
     */
    public function setDownloadRemote(bool $download): self
    {
        $this->downloadRemote = (bool) $download;

        return $this;
    }

    /**
     * Gets file metadata using recursive if specified
     *
     * @link    https://wiki.apache.org/tika/TikaJAXRS#Recursive_Metadata_and_Content
     * @param   string  $file
     * @param   string  $recursive
     * @return  \Vaites\ApacheTika\Metadata\Metadata|\Vaites\ApacheTika\Metadata\DocumentMetadata|\Vaites\ApacheTika\Metadata\ImageMetadata
     * @throws  \Exception
     */
    public function getMetadata(string $file, string $recursive = null): ?Metadata
    {
        if(is_null($recursive))
        {
            $response = $this->request('meta', $file);
        }
        elseif(in_array($recursive, ['text', 'html', 'ignore']))
        {
            $response = $this->request("rmeta/$recursive", $file);
        }
        else
        {
            throw new Exception("Unknown recursive type (must be text, html, ignore or null)");
        }

        return Metadata::make($response, $file);
    }

    /**
     * Gets recursive file metadata (alias for getMetadata)
     *
     * @param   string  $file
     * @param   string  $recursive
     * @return  \Vaites\ApacheTika\Metadata\Metadata
     * @throws  \Exception
     */
    public function getRecursiveMetadata(string $file, string $recursive)
    {
        return $this->getMetadata($file, $recursive);
    }

    /**
     * Detect language
     *
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function getLanguage(string $file): string
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
    public function getMIME(string $file): string
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
    public function getHTML(string $file, callable $callback = null): string
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
    public function getText(string $file, callable $callback = null): string
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
     * @param   string      $file
     * @param   callable    $callback
     * @return  string
     * @throws  \Exception
     */
    public function getMainText(string $file, callable $callback = null): string
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
     * @return  string
     * @throws  \Exception
     */
    public function getVersion(): string
    {
        return $this->request('version');
    }

    /**
     * Return the list of Apache Tika supported versions
     *
     * @return array
     */
    public static function getSupportedVersions(): array
    {
        return self::$supportedVersions;
    }

    /**
     * Sets the checked flag
     *
     * @param   bool    $checked
     * @return  $this
     */
    public function setChecked(bool $checked): self
    {
        $this->checked = (bool) $checked;

        return $this;
    }

    /**
     * Checks if instance is checked
     *
     * @return  bool
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * Check if a response is cached
     *
     * @param   string  $type
     * @param   string  $file
     * @return  bool
     */
    protected function isCached(string $type, string $file): bool
    {
        return isset($this->cache[sha1($file)][$type]);
    }

    /**
     * Get a cached response
     *
     * @param   string  $type
     * @param   string  $file
     * @return  mixed
     */
    protected function getCachedResponse(string $type, string $file)
    {
        return $this->cache[sha1($file)][$type] ?? null;
    }

    /**
     * Check if a request type must be cached
     *
     * @param   string  $type
     * @return  bool
     */
    protected function isCacheable(string $type): bool
    {
        return in_array($type, ['lang', 'meta']);
    }

    /**
     * Caches a response
     *
     * @param   string  $type
     * @param   mixed   $response
     * @param   string  $file
     * @return  bool
     */
    protected function cacheResponse(string $type, $response, string $file): bool
    {
        $this->cache[sha1($file)][$type] = $response;

        return true;
    }

    /**
     * Checks if a specific version is supported
     *
     * @param   string  $version
     * @return  bool
     */
    public static function isVersionSupported(string $version): bool
    {
        return in_array($version, self::getSupportedVersions());
    }

    /**
     * Check the request before executing
     *
     * @param   string  $type
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function checkRequest(string $type, string $file = null): ?string
    {
        // no checks for getters
        if(in_array($type, ['detectors', 'mime-types', 'parsers', 'version']))
        {
            //
        }
        // invalid local file
        elseif(!preg_match('/^http/', $file) && !file_exists($file))
        {
            throw new Exception("File $file can't be opened");
        }
        // invalid remote file
        elseif(preg_match('/^http/', $file) && !preg_match('/200/', get_headers($file)[0]))
        {
            throw new Exception("File $file can't be opened", 2);
        }
        // download remote file if required only for integrated downloader
        elseif(preg_match('/^http/', $file) && $this->downloadRemote)
        {
            $file = $this->downloadFile($file);
        }

        return $file;
    }

    /**
     * Download file to a temporary folder
     *
     * @link    https://wiki.apache.org/tika/TikaJAXRS#Specifying_a_URL_Instead_of_Putting_Bytes
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    protected function downloadFile(string $file): string
    {
        $dest = tempnam(sys_get_temp_dir(), 'TIKA');

        $fp = fopen($dest, 'w+');

        if($fp === false)
        {
            throw new Exception("$dest can't be opened");
        }

        $ch = curl_init($file);
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
     * @return  array
     * @throws  \Exception
     */
    abstract public function getSupportedMIMETypes(): array;

    /**
     * Must return the available detectors
     *
     * @return  array
     * @throws  \Exception
     */
    abstract public function getAvailableDetectors(): array;

    /**
     * Must return the available parsers
     *
     * @return  array
     * @throws  \Exception
     */
    abstract public function getAvailableParsers(): array;

    /**
     * Check Java binary, JAR path or server connection
     *
     * @return  void
     */
    abstract public function check(): void;

    /**
     * Configure and make a request and return its results.
     *
     * @param   string  $type
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    abstract public function request(string $type, string $file = null): string;
}
