<?php

namespace Vaites\ApacheTika\Clients;

use Exception;

use Vaites\ApacheTika\Client;

/**
 * Apache Tika command line interface client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://tika.apache.org/1.23/gettingstarted.html#Using_Tika_as_a_command_line_utility
 */
class CLIClient extends Client
{
    protected const MODE = 'cli';

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
     * @throws \Exception
     */
    public function __construct(string $path = null, string $java = null, bool $check = true)
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
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Set the path
     */
    public function setPath($path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the Java path
     */
    public function getJava(): ?string
    {
        return $this->java;
    }

    /**
     * Set the Java path
     */
    public function setJava($java): self
    {
        $this->java = $java;

        return $this;
    }

    /**
     * Returns the supported MIME types
     *
     * NOTE: the data provided by the CLI must be parsed: mime type has no spaces, aliases go next prefixed with spaces
     *
     * @throws \Exception
     */
    public function getSupportedMIMETypes(): array
    {
        $mime = null;
        $mimeTypes = [];

        $response = preg_split("/\n/", $this->request('mime-types'));

        foreach($response as $line)
        {
            if(preg_match('/^\w+/', $line))
            {
                $mime = $line;
                $mimeTypes[$mime] = ['alias' => []];
            }
            else
            {
                [$key, $value] = preg_split('/:\s+/', trim($line));

                if($key == 'alias')
                {
                    $mimeTypes[$mime]['alias'][] = $value;
                }
                else
                {
                    $mimeTypes[$mime][$key] = $value;
                }
            }
        }


        return $mimeTypes;
    }

    /**
     * Returns the available detectors
     *
     * @throws \Exception
     */
    public function getAvailableDetectors(): array
    {
        $response = $this->request('detectors');

        return preg_split("/\n/", $response);
    }

    /**
     * Returns the available parsers
     *
     * @throws \Exception
     */
    public function getAvailableParsers(): array
    {
        $response = $this->request('parsers');

        return preg_split("/\n/", $response);
    }


    /**
     * Check Java binary, JAR path or server connection
     *
     * @throws \Exception
     */
    public function check(): void
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
     * @throws \Exception
     */
    public function request(string $type, string $file = null): string
    {
        // check if not checked
        $this->check();

        // check if is cached
        if($file !== null && $this->isCached($type, $file))
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
     * @throws \Exception
     */
    public function exec(string $command): ?string
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
     * @throws  Exception
     */
    protected function getArguments(string $type, string $file = null): array
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

            case 'rmeta/ignore':
            case 'rmeta/html':
            case 'rmeta/text':
                throw new Exception('Recursive metadata is not supported in command line mode');

            default:
                throw new Exception($file ? "Unknown type $type for $file" : "Unknown type $type");
        }

        return $arguments;
    }
}
