<?php declare(strict_types=1);

namespace Vaites\ApacheTika;

use Vaites\ApacheTika\Contracts\Client as ClientContract;
use Vaites\ApacheTika\Contracts\Entity as EntityContract;
use Vaites\ApacheTika\Contracts\Metadata as MetadataContract;
use Vaites\ApacheTika\Exceptions\Exception;

/**
 * Base entity
 *
 * @property-read null|string $html
 * @property-read null|string $mainText
 * @property-read null|string $mime
 * @property-read null|string $text
 * @property-read null|string $xhtml
 * @property-read MetadataContract $metadata
 *
 * @method Contracts\Entity|Entities\Book|Entities\Document|Entities\Image|Entities\Text make(string $path, mixed ...$args)
 */
abstract class Entity implements EntityContract
{
    /**
     * File path or URL
     */
    protected string $path;

    /**
     * Apache Tika client instance
     */
    protected ClientContract $client;

    /**
     * Metadata instance
     */
    protected MetadataContract $metadata;

    /**
     * Create a new entity instance
     */
    final public function __construct(string $path, mixed ...$args)
    {
        $this->path = $path;

        $this->client = static::client(...$args);
    }

    /**
     * Dynamically get properties from metadata or calling client
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
     */
    public function __get(string $name): mixed
    {
        return match(true)
        {
            $name === 'html'        => $this->client->getHTML($this->path),
            $name === 'mainText'    => $this->client->getMainText($this->path),
            $name === 'mime'        => $this->client->getMIME($this->path),
            $name === 'text'        => $this->client->getText($this->path),
            $name === 'xhtml'       => $this->client->getXHTML($this->path),
            $name === 'metadata'    => $this->metadata(),

            property_exists($this->metadata(), $name) => $this->metadata()->$name,
            default => throw new Exception(sprintf('Undefined property %s::$%s', static::class, $name))
        };
    }

    /**
     * Return an instance of the entity guessing the type
     */
    public static function make(string $path, mixed ...$args): EntityContract
    {
        if(filter_var($path, FILTER_VALIDATE_URL))
        {
            $mime = get_headers($path)['Content-Type'] ?? null;
        }
        elseif(extension_loaded('fileinfo'))
        {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            $mime = $finfo ? finfo_file($finfo, $path) : null;
        }
        else
        {
            $mime = static::client(...$args)->getMIME($path);
        }

        $entity = MIME::guess($mime)->entity();

        return new $entity($path, ...$args);
    }

    /**
     * Get and load the file metadata
     */
    protected function metadata(): MetadataContract
    {
        if(!isset($this->metadata))
        {
            $this->metadata = $this->client->getMetadata($this->path);
        }

        return $this->metadata;
    }

    /**
     * Return an instance of the client
     */
    protected static function client(mixed ...$args): ClientContract
    {
        return match(true)
        {
            empty($args)                => Client::make(),
            $args[0] instanceof Client  => $args[0],
            default                     => Client::make(...$args)
        };
    }
}