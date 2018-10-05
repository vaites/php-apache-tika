<?php

namespace Vaites\ApacheTika\Clients;

use Exception;

use Vaites\ApacheTika\Client;
use Vaites\ApacheTika\Metadata\Metadata;

/**
 * Apache Tika command line interface client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    http://wiki.apache.org/tika/TikaJAXRS
 * @link    https://tika.apache.org/1.12/formats.html
 */
class CLIClient extends Client
{
    const MODE = 'cli';

    /**
     * Apache Tika app path
     *
     * @var string
     */
    protected $path = null;

    /**
     * Java binary path
     *
     * @var string
     */
    protected $java = null;

    /**
     * Configure client
     *
     * @param   string  $path
     * @param   string  $java
     *
     * @throws Exception
     */
    public function __construct($path = null, $java = null)
    {
        if($path)
        {
            $this->setPath($path);
        }

        if($java)
        {
            $this->setJava($java);
        }
    }

    /**
     * Get the path
     *
     * @return  null|string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path
     *
     * @param   string  $path
     * @return  $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the Java path
     *
     * @return  null|int
     */
    public function getJava()
    {
        return $this->java;
    }

    /**
     * Set the Java path
     *
     * @param   string    $java
     * @return  $this
     */
    public function setJava($java)
    {
        $this->java = $java;

        return $this;
    }

    /**
     * Configure and make a request and return its results
     *
     * @param   string  $type
     * @param   string  $file
     * @return  string
     * @throws  \Exception
     */
    public function request($type, $file = null)
    {
        // check if is cached
        if(isset($this->cache[sha1($file)][$type]))
        {
            return $this->cache[sha1($file)][$type];
        }

        // command arguments
        $arguments = $this->getArguments($type, $file);

        // check the request
        $file = parent::checkRequest($type, $file);

        // add last argument
        if($file)
        {
            $arguments[] = escapeshellarg($file);
        }

        // build command
        $jar = escapeshellarg($this->path);
        $command = ($this->java ?: 'java') . " -jar $jar " . implode(' ', $arguments);

        // run command
        $response = $this->exec($command);

        // metadata response
        if($type == 'meta')
        {
            // fix for invalid? json returned only with images
            $response = str_replace(basename($file) . '"}{', '", ', $response);

            // on Windows, response comes in another charset
            if(defined('PHP_WINDOWS_VERSION_MAJOR'))
            {
                $response = utf8_encode($response);
            }

            $response = Metadata::make($response, $file);
        }

        // cache certain responses
        if(in_array($type, ['lang', 'meta']))
        {
            $this->cache[sha1($file)][$type] = $response;
        }

        return $response;
    }

    /**
     * Run the command and return its results
     *
     * @param   string  $command
     * @return  null|string
     * @throws  \Exception
     */
    public function exec($command)
    {
        // run command
        $exit = -1;
        $logfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tika-error.log';
        $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['file', $logfile, 'a']];
        $process = proc_open($command, $descriptors, $pipes);
        $callback = $this->callback;

        // get output if command runs ok
        if(is_resource($process))
        {
            fclose($pipes[0]);
            $this->response = '';
            while($chunk = stream_get_line($pipes[1], $this->chunkSize))
            {
                if(!is_null($callback))
                {
                    $callback($chunk);
                }

                $this->response .= $chunk;
            }
            fclose($pipes[1]);
            $exit = proc_close($process);
        }

        // exception if exit value is not zero
        if($exit > 0)
        {
            throw new Exception("Unexpected exit value ($exit) for command $command");
        }

        return trim($this->response);
    }

    /**
     * Get the arguments to run the command
     *
     * @param   string  $type
     * @param   string  $file
     * @return  array
     * @throws  Exception
     */
    protected function getArguments($type, $file = null)
    {
        // parameters for command
        $arguments = [];
        switch($type)
        {
            case 'html':
                $arguments[] = '--html';
                break;

            case 'lang':
                $arguments[] = '--language';
                break;

            case 'mime':
                $arguments[] = '--detect';
                break;

            case 'meta':
                $arguments[] = '--metadata --json';
                break;

            case 'text':
                $arguments[] = '--text';
                break;

            case 'text-main':
                $arguments[] = '--text-main';
                break;

            case 'mime-types':
                $arguments[] = '--list-supported-types';
                break;

            case 'detectors':
                $arguments[] = '--list-detectors';
                break;

            case 'parsers':
                $arguments[] = '--list-parsers';
                break;

            case 'version':
                $arguments[] = '--version';
                break;

            default:
                throw new Exception("Unknown type $type");
        }

        return $arguments;
    }
}
