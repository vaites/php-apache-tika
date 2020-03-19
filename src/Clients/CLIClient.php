<?php

namespace Vaites\ApacheTika\Clients;

use Exception;

use Vaites\ApacheTika\Client;

/**
 * Apache Tika command line interface client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://tika.apache.org/1.24/gettingstarted.html#Using_Tika_as_a_command_line_utility
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
     * @param   bool    $check
     * @throws  \Exception
     */
    public function __construct($path = null, $java = null, $check = true)
    {
        parent::__construct();

        if($path)
        {
            $this->setPath($path);
        }

        if($java)
        {
            $this->setJava($java);
        }

        if($check === true)
        {
            $this->check();
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
     * Check Java binary, JAR path or server connection
     *
     * @return  void
     * @throws  \Exception
     */
    public function check()
    {
        if($this->isChecked() === false)
        {
            // Java command must not return an error
            try
            {
                $this->exec(($this->java ?: 'java') . ' -version');
            }
            catch(Exception $exception)
            {
                throw new Exception('Java command not found');
            }

            // JAR path must exists
            if(file_exists($this->path) === false)
            {
                throw new Exception('Apache Tika app JAR not found');
            }

            $this->setChecked(true);
        }
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
        // check if not checked
        $this->check();

        // check if is cached
        if($this->isCached($type, $file))
        {
            return $this->getCachedResponse($type, $file);
        }

        // command arguments
        $arguments = $this->getArguments($type, $file);

        // check the request
        $file = $this->checkRequest($type, $file);

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

            // on Windows, response must be encoded to UTF8
            $response = $this->platform == 'win' ? utf8_encode($response) : $response;
        }

        // cache certain responses
        if($this->isCacheable($type))
        {
            $this->cacheResponse($type, $response, $file);
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

                if($this->callbackAppend === true)
                {
                    $this->response .= $chunk;
                }
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
        $arguments = $this->encoding ? ["--encoding={$this->encoding}"] : [];

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

            case 'rmeta/ignore':
            case 'rmeta/html':
            case 'rmeta/text':
                throw new Exception('Recursive metadata is not supported in command line mode');
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
                throw new Exception($file ? "Unknown type $type for $file" : "Unknown type $type");
        }

        return $arguments;
    }
}
