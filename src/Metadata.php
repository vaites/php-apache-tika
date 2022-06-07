<?php declare(strict_types=1);

namespace Vaites\ApacheTika;

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
    public stdClass $meta;

    /**
     * Timezone
     */
    protected DateTimeZone $timezone;

    /**
     * Parse Apache Tika response filling all properties
     *
     * @throws \Exception
     */
    public function __construct(stdClass $meta, string $file, string $timezone)
    {
        $this->meta = $meta;
        $this->timezone = new DateTimeZone($timezone);
        
        // process each meta
        foreach((array) $this->meta as $key => $value)
        {
            if(!empty($value) && is_string($value))
            {
                $this->setAttribute($key, $value);
            }
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
    public static function make(stdClass $meta, string $file, string $timezone): Contract
    {
        // get content type
        try
        {
            $mime = is_array($meta->{'Content-Type'}) ? current($meta->{'Content-Type'}) : $meta->{'Content-Type'};
        }
        catch(\Exception $exception)
        {
            $mime = 'application/octet-stream';
        }

        // instance based on content type
        $instance = match(current(explode('/', $mime)))
        {
            'image' => new Metadata\Image($meta, $file, $timezone),
            default => new Metadata\Document($meta, $file, $timezone)
        };

        return $instance;
    }

    /**
     * Sets an attribute
     *
     * @throws \Exception
     */
    public final function setAttribute(string $key, string $value): Contract
    {
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
                $value = (string) preg_replace('/\.\d+/', 'Z', $value);
                $this->created = new DateTime($value);
                $this->created->setTimezone($this->timezone);
                break;

            case 'dcterms:modified':
            case 'last-modified':
            case 'modified':
                $value = (string) preg_replace('/\.\d+/', 'Z', $value);
                $this->updated = new DateTime($value);
                $this->updated->setTimezone($this->timezone);
                break;

            default:
                $this->setSpecificAttribute($key, $value);

        }

        return $this;
    }

    /**
     * Sets an speficic attribute for the file type
     */
    abstract protected function setSpecificAttribute(string $key, string $value): Contract;
}
