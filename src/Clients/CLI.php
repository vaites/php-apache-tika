<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Clients;

use Vaites\ApacheTika\Exceptions\Exception;
use Vaites\ApacheTika\Client;

/**
 * Apache Tika command line interface client
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 * @link    https://tika.apache.org/2.7.0/gettingstarted.html#Using_Tika_as_a_command_line_utility
 */
class CLI extends Client
{
    /**
     * Apache Tika app path
     */
    protected string $path;

    /**
     * Java binary path
     */
    protected string $java;

    /**
     * Java arguments
     */
    protected array $javaArgs;

    /**
     * Environment variables
     *
     * @var array
     */
    protected array $envVars;

    /**
     * Configure client
     * 
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function __construct(string $path = null, string $java = null, bool $check = null)
    {
        parent::__construct();

        if($path === null && getenv('APACHE_TIKA_PATH') !== false)
        {
            $path = getenv('APACHE_TIKA_PATH');
        }

        if($java === null && getenv('JAVA_HOME'))
        {
            $java = getenv('JAVA_HOME') . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'java';
        }

        if($path !== null)
        {
            $this->setPath($path);
        }

        if($java !== null)
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
        return $this->path ?? null;
    }

    /**
     * Set the path
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the Java path
     */
    public function getJava(): ?string
    {
        return $this->java ?? null;
    }

    /**
     * Set the Java path
     */
    public function setJava(string $java): self
    {
        $this->java = $java;

        return $this;
    }

    /**
     * Get the Java arguments
     */
    public function getJavaArgs(): array
    {
        return $this->javaArgs ?? [];
    }

    /**
     * Set the Java arguments
     *
     * NOTE: to modify child process jvm args, prepend "J" to each argument (-JXmx4g)
     */
    public function setJavaArgs(array $args): self
    {
        $this->javaArgs = $args;

        return $this;
    }

    /**
     * Get the environment variables
     */
    public function getEnvVars(): array
    {
        return $this->envVars ?? [];
    }

    /**
     * Set the environment variables
     */
    public function setEnvVars(array $variables): self
    {
        $this->envVars = $variables;

        return $this;
    }

    /**
     * Returns current Tika version
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getVersion(bool $request = false): string
    {
        $manifest = [];

        if($request === true || !isset($this->version))
        {
            $path = $this->getPath();

            // try to get version using MANIFEST.MF file inside Apache Tika's JAR file
            if($path !== null && file_exists($path) && extension_loaded('zip'))
            {
                try
                {
                    $zip = new \ZipArchive();

                    if($zip->open($path))
                    {
                        $content = $zip->getFromName('META-INF/MANIFEST.MF') ?: 'ERROR';
                        if(preg_match_all('/(.+):\s+(.+)\r?\n/U', $content, $match))
                        {
                            foreach($match[1] as $index => $key)
                            {
                                $manifest[$key] = $match[2][$index];
                            }
                        }
                    }
                }
                catch(\Throwable $exception)
                {
                    //
                }
            }

            $this->setVersion($manifest['Implementation-Version'] ?? $this->request('version'));
        }

        return $this->version;
    }

    /**
     * Returns the supported MIME types
     *
     * NOTE: the data provided by the CLI must be parsed: mime type has no spaces, aliases go next prefixed with spaces
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getSupportedMIMETypes(): array
    {
        $mime = null;
        $mimeTypes = [];

        $response = preg_split("/\n/", $this->request('mime-types')) ?: [];

        foreach($response as $line)
        {
            if(preg_match('/^\w+/', $line))
            {
                $mime = trim($line);
                $mimeTypes[$mime] = ['alias' => []];
            }
            else
            {
                [$key, $value] = (preg_split('/:\s+/', trim($line)) ?: ['error', 'error']);

                if($key === 'alias' && is_array($mimeTypes[$mime]['alias']))
                {
                    $mimeTypes[$mime]['alias'][] = $value;
                }
                elseif($key !== 'error')
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
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function getAvailableDetectors(): array
    {
        $detectors = [];

        $split = preg_split("/\n/", $this->request('detectors')) ?: [];

        $parent = null;
        foreach($split as $line)
        {
            if(preg_match('/composite/i', $line))
            {
                $parent = trim(preg_replace('/\(.+\):/', '', $line) ?: '');
                $detectors[$parent] = ['children' => [], 'composite' => true, 'name' => $parent];
            }
            else
            {
                $child = trim($line);
                $detectors[$parent]['children'][$child] = ['composite' => false, 'name' => $child];
            }
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
        $parsers = [];

        $split = preg_split("/\n/", $this->request('parsers')) ?: [];
        array_shift($split);

        $parent = null;
        foreach($split as $line)
        {
            if(preg_match('/composite/i', $line))
            {
                $parent = trim(preg_replace('/\(.+\):/', '', $line) ?: '');

                $parsers[$parent] = ['children' => [], 'composite' => true, 'name' => $parent, 'decorated' => false];
            }
            else
            {
                $child = trim($line);

                $parsers[$parent]['children'][$child] = ['composite' => false, 'name' => $child, 'decorated' => false];
            }
        }

        return $parsers;
    }

    /**
     * Check Java binary and JAR path
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function check(): void
    {
        if($this->isChecked() === false)
        {
            // Java command must not return an error
            try
            {
                $this->exec(($this->getJava() ?: 'java') . ' -version');
            }
            catch(Exception $exception)
            {
                throw new Exception('Java command not found');
            }

            // JAR path must exist
            if($this->getPath() !== null && file_exists($this->getPath()) === false)
            {
                throw new Exception('Apache Tika app JAR not found on ' . $this->getPath());
            }

            $this->setChecked(true);
        }
    }

    /**
     * Configure and make a request and return its results
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function request(string $type, string $file = null): string
    {
        // check if not checked
        $this->check();

        // check if is cached
        if($file !== null && $this->isCached($type, $file))
        {
            return (string) $this->getCachedResponse($type, $file);
        }

        // command arguments
        $arguments = array_merge($this->getJavaArgs(), $this->getArguments($type, $file));

        // check the request
        $file = $this->checkRequest($type, $file);

        // add last argument
        if($file !== null)
        {
            $arguments[] = escapeshellarg($file);
        }

        // build command
        $jar = escapeshellarg($this->getPath() ?: 'error');
        $java = trim($this->getJava() ?: 'java');
        $command = trim(sprintf('%s -jar %s %s', $java, $jar, implode(' ', $arguments)));

        // run command
        $response = $this->exec($command);

        // error if command fails
        if($response === null)
        {
            throw new Exception('An error occurred running Java command');
        }

        // metadata response
        if($file !== null && in_array(preg_replace('/\/.+/', '', $type), ['meta', 'rmeta']))
        {
            // fix for invalid? json returned only with images
            $response = str_replace(basename($file) . '"}{', '", ', $response);

            // on Windows, response must be encoded to UTF8
            if(version_compare($this->getVersion(), '2.1.0', '<'))
            {
                $response = $this->platform == 'win' ? utf8_encode($response) : $response;
            }
        }

        // cache certain responses
        if($file !== null && $this->isCacheable($type))
        {
            $this->cacheResponse($type, $response, $file);
        }

        return $this->filterResponse($response);
    }

    /**
     * Run the command and return its results
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function exec(string $command): ?string
    {
        // get env variables for proc_open()
        $env = array_merge(getenv(), $this->getEnvVars());

        // run command
        $exit = -1;
        $logfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tika-error.log';
        $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['file', $logfile, 'a']];
        $process = proc_open($command, $descriptors, $pipes, null, $env);
        $callback = $this->callback ?? null;

        // get output if command runs ok
        if(is_resource($process))
        {
            fclose($pipes[0]);
            $this->response = '';
            while($chunk = stream_get_line($pipes[1], $this->getChunkSize()))
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

        return isset($this->response) ? $this->filterResponse($this->response) : null;
    }

    /**
     * Get the arguments to run the command
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    protected function getArguments(string $type, string $file = null): array
    {
        $encoding = $this->getEncoding();

        $arguments = $encoding ? ["--encoding=$encoding"] : [];
        $arguments[] = match($type)
        {
            'html'          => '--html',
            'lang'          => '--language',
            'mime'          => '--detect',
            'meta'          => '--metadata --json',
            'text'          => '--text',
            'text-main'     => '--text-main',
            'mime-types'    => '--list-supported-types',
            'detectors'     => '--list-detectors',
            'parsers'       => '--list-parsers',
            'version'       => '--version',
            'rmeta/ignore'  => '--metadata --jsonRecursive',
            'rmeta/html'    => '--html --jsonRecursive',
            'rmeta/text'    => '--text --jsonRecursive',
            'xhtml'         => '--xml',
            default         => throw new Exception($file ? "Unknown type $type for $file" : "Unknown type $type")
        };

        return $arguments;
    }
}
