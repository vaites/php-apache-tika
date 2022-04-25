<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

use DateTime;
use DateTimeZone;
use Exception;
use stdClass;

use Vaites\ApacheTika\Contracts\Metadata as Contract;

/**
 * Standarized metadata class with common attributes for all document types
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
abstract class Metadata implements Contract
{
    /**
     * Title
     */
    public ?string $title = null;

    /**
     * Content
     */
    public ?string $content = null;

    /**
     * MIME type
     */
    public ?string $mime = null;

    /**
     * Date created
     */
    public ?DateTime $created = null;

    /**
     * Date updated or last modified
     */
    public ?DateTime $updated = null;

    /**
     * RAW attributes returned by Apache Tika
     */
    public ?stdClass $meta = null;

    /**
     * Parse Apache Tika response filling all properties
     *
     * @throws \Exception
     */
    public function __construct(stdClass $meta, string $file)
    {
        $this->meta = $meta;

        // process each meta
        foreach((array) $this->meta as $key => $value)
        {
            $this->setAttribute($key, $value);
        }

        // file name without extension if title is not detected
        if(empty($this->title))
        {
            $this->title = (string) preg_replace('/\..+$/', '', basename($file));
        }

        // use creation date as last modified if not detected
        if(empty($this->updated))
        {
            $this->updated = $this->created;
        }
    }

    /**
     * Return an instance of Metadata based on content type
     *
     * @throws \Exception
     */
    public static function make(stdClass $meta, string $file): Contract
    {
        // get content type
        $mime = is_array($meta->{'Content-Type'}) ? current($meta->{'Content-Type'}) : $meta->{'Content-Type'};

        // instance based on content type
        switch(current(explode('/', $mime)))
        {
            case 'image':
                $instance = new ImageMetadata($meta, $file);
                break;

            default:
                $instance = new DocumentMetadata($meta, $file);
        }

        return $instance;
    }

    /**
     * Sets an attribute
     *
     * @param mixed $value
     * @throws \Exception
     */
    public final function setAttribute(string $key, $value): Contract
    {
        $timezone = new DateTimeZone('UTC');

        switch(mb_strtolower($key))
        {
            case 'content-type':
                $mime = $value ? (preg_split('/;\s+/', $value) ?: []) : [];

                if(count($mime))
                {
                    $this->mime = array_shift($mime);
                }
                break;

            case 'creation-date':
            case 'date':
            case 'dcterms:created':
            case 'meta:creation-date':
                $value = preg_replace('/\.\d+/', 'Z', $value);
                $this->created = new DateTime(is_array($value) ? array_shift($value) : $value, $timezone);
                break;

            case 'dcterms:modified':
            case 'last-modified':
            case 'modified':
                $value = preg_replace('/\.\d+/', 'Z', $value);
                $this->updated = new DateTime(is_array($value) ? array_shift($value) : $value, $timezone);
                break;

            default:
                $this->setSpecificAttribute($key, $value);

        }

        return $this;
    }

    /**
     * Sets an speficic attribute for the file type
     *
     * @param mixed $value
     */
    abstract protected function setSpecificAttribute(string $key, $value): Contract;
}
