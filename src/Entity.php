<?php declare(strict_types=1);

namespace Vaites\ApacheTika;

use Vaites\ApacheTika\Contracts\Client as ClientContract;
use Vaites\ApacheTika\Contracts\Entity as EntityContract;
use Vaites\ApacheTika\Contracts\Metadata as MetadataContract;
use Vaites\ApacheTika\Entities\Document;
use Vaites\ApacheTika\Entities\Image;
use Vaites\ApacheTika\Entities\Video;

/**
 * Base entity
 *
 * @property-read null|string $html
 * @property-read null|string $mainText
 * @property-read null|string $mime
 * @property-read null|string $text
 * @property-read null|string $xhtml
 * @property-read MetadataContract $metadata
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
    public function __construct(string $path, ...$args)
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
        return match($name)
        {
            'html'      => $this->client->getHTML($this->path),
            'mainText'  => $this->client->getMainText($this->path),
            'metadata'  => $this->metadata(),
            'mime'      => $this->client->getMIME($this->path),
            'text'      => $this->client->getText($this->path),
            'xhtml'     => $this->client->getXHTML($this->path),
            default     => $this->metadata()->$name ?? null
        };
    }

    /**
     * Return an instance of the entity guessing the type
     */
    public static function make(string $path, ...$args): EntityContract
    {
        return new static($path, ...$args);
    }

    /**
     * Guess the entity type and return an instance
     */
    public static function guess(string $path, ...$args): EntityContract
    {
        $mime = match(true)
        {
            filter_var($path, FILTER_VALIDATE_URL)  => get_headers($path)['Content-Type'] ?? null,
            extension_loaded('fileinfo')            => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path),
            default                                 => static::client(...$args)->getMIME($path)
        };

        $entity = match(true)
        {
            str_contains($mime, 'image/')   => Image::class,
            str_contains($mime, 'video/')   => Video::class,
            default                         => Document::class,
        };

        return $entity::make($path, ...$args);
    }

    /**
     *
     *
     * @throws \Vaites\ApacheTika\Exceptions\Exception
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
    protected static function client(...$args): ClientContract
    {
        return match(true)
        {
            empty($args)                => Client::make(),
            $args[0] instanceof Client  => $args[0],
            default                     => Client::make(...$args)
        };
    }
}