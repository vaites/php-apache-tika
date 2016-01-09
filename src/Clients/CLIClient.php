<?php namespace Vaites\ApacheTika\Clients;

use Exception;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Metadata\Metadata;

/**
 * Apache Tika command line interface client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.11/formats.html
 * @package Vaites\ApacheTika
 */
class CLIClient extends Client
{
    /**
     * Apache Tika app path
     *
     * @var string
     */
    protected $path = null;

    /**
     * Is server running?
     *
     * @param   string  $path
     * @throws  Exception
     */
    public function __construct($path = null)
    {
        $this->path = realpath($path);

        if(!file_exists($this->path))
        {
            throw new Exception("Apache Tika JAR not found ({$this->path})");
        }
    }

    /**
     * Configure and make a request and return its results
     *
     * @param   string  $file
     * @param   string  $type
     * @return  string
     * @throws  \Exception
     */
    protected function request($file, $type)
    {
        // check if is cached
        if(isset($this->cache[sha1($file)][$type]))
        {
            return $this->cache[sha1($file)][$type];
        }

        // parameters for cURL request
        $arguments = [];
        switch($type)
        {
            case 'html':
                $arguments[] = '--html';
                break;

            case 'mime':
                $arguments[] = "--detect";
                break;

            case 'lang':
                $arguments[] = '--language';
                break;

            case 'meta':
                $arguments[] = '--metadata --json';
                break;

            case 'text':
                $arguments[] = '--text';
                break;

            case 'version':
                $arguments[] = '--version';
                break;

            default:
                throw new Exception("Unknown type $type");
        }

        // invalid file
        if ($file && !preg_match('/^http/', $file) && !file_exists($file)) {
            throw new Exception("File $file can't be opened");
        }

        // add last argument
        if ($file) {
            $arguments[] = "'$file'";
        }

        // build command
        $command = "java -jar '{$this->path}' ".implode(' ', $arguments);

        // run command and process output
        $response = trim(shell_exec($command));

        // metadata response
        if ($type == 'meta') {
            // fix for invalid? json returned only with images
            $response = str_replace(basename($file).'"}{', '", ', $response);

            $response = Metadata::make($response, $file);
        }

        // cache certain responses
        if (in_array($type, ['lang', 'meta'])) {
            $this->cache[sha1($file)][$type] = $response;
        }

        return $response;
    }
}
