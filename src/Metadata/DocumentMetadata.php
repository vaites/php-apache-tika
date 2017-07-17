<?php

namespace Vaites\ApacheTika\Metadata;

use DateTime;
use DateTimeZone;

/**
 * Metadata class for documents
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
class DocumentMetadata extends Metadata
{
    /**
     * Title (if not detected by Apache Tika, name without extension is used)
     *
     * @var string
     */
    public $title = null;

    /**
     * Description.
     *
     * @var string
     */
    public $description = null;

    /**
     * Keywords
     *
     * @var string
     */
    public $keywords = [];

    /**
     * Two-letter language code (ISO-639-1)
     *
     * @link https://en.wikipedia.org/wiki/ISO_639-1
     *
     * @var string
     */
    public $language = null;

    /**
     * Author
     *
     * @var string
     */
    public $author = null;

    /**
     * Software used to generate document
     *
     * @var string
     */
    public $generator = null;

    /**
     * Number of pages
     *
     * @var int
     */
    public $pages = 0;

    /**
     * Number of words.
     *
     * @var int
     */
    public $words = 0;

    /**
     * Sets an attribute
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  bool
     */
    protected function setAttribute($key, $value)
    {
        $timezone = new DateTimeZone('UTC');

        if(is_array($value))
        {
            $value = array_shift($value);
        }

        switch(mb_strtolower($key))
        {
            case 'title':
                $this->title = $value;
                break;

            case 'comments':
                $this->description = $value;
                break;

            case 'keyword':
            case 'keywords':
                if(preg_match('/,/', $value))
                {
                    $value = preg_split('/\s*,\s*/', $value);
                }
                else
                {
                    $value = preg_split('/\s+/', $value);
                }
                $this->keywords = array_unique($value);
                break;

            case 'language':
                $this->language = mb_substr($value, 0, 2);
                break;

            case 'author':
            case 'initial-creator':
                $this->author = $value;
                break;

            case 'content-type':
                $value = preg_split('/;\s+/', $value);
                $this->mime = array_shift($value);
                break;

            case 'application-name':
            case 'generator':
            case 'producer':
                $value = preg_replace('/\$.+/', '', $value);
                $this->generator = trim($value);
                break;

            case 'nbpage':
            case 'page-count':
            case 'xmptpg:npages':
                $this->pages = (int) $value;
                break;

            case 'nbword':
            case 'word-count':
                $this->words = (int) $value;
                break;

            case 'creation-date':
            case 'date':
                $value = preg_replace('/\.\d+/', 'Z', $value);
                $this->created = new DateTime($value, $timezone);
                break;

            case 'last-modified':
                $value = preg_replace('/\.\d+/', 'Z', $value);
                $this->updated = new DateTime($value, $timezone);
                break;

            default:
                return false;
        }

        return true;
    }
}
