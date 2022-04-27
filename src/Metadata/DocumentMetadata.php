<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

use Vaites\ApacheTika\Contracts\Metadata as Contract;

/**
 * Metadata class for documents
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
class DocumentMetadata extends Metadata
{
    /**
     * Title (if not detected by Apache Tika, name without extension is used)
     */
    public ?string $title = null;

    /**
     * Description
     */
    public ?string $description = null;

    /**
     * Keywords
     */
    public array $keywords = [];

    /**
     * Two-letter language code (ISO-639-1)
     *
     * @link https://en.wikipedia.org/wiki/ISO_639-1
     */
    public ?string $language = null;

    /**
     * Content encoding
     */
    public ?string $encoding = null;

    /**
     * Author
     */
    public ?string $author = null;

    /**
     * Software used to generate document
     */
    public ?string $generator = null;

    /**
     * Number of pages
     */
    public int $pages = 0;

    /**
     * Number of words
     */
    public int $words = 0;

    /**
     * Sets an attribute
     *
     * @param mixed $value
     * @throws  \Exception
     */
    protected function setSpecificAttribute(string $key, $value): Contract
    {
        if(is_array($value))
        {
            $value = array_shift($value);
        }

        switch(mb_strtolower($key))
        {
            case 'dc:title':
            case 'title':
                $this->title = $value;
                break;

            case 'comments':
            case 'dc:description':
            case 'description':
            case 'w:comments':
                $this->description = $value;
                break;

            case 'keyword':
            case 'keywords':
            case 'meta:keyword':
            case 'pdf:docinfo:keywords':
                $keywords = preg_split(preg_match('/,/', $value) ? '/\s*,\s*/' : '/\s+/', $value);
                $this->keywords = array_unique($keywords ?: []);
                break;

            case 'language':
                $this->language = mb_substr($value, 0, 2);
                break;

            case 'author':
            case 'dc:creator':
            case 'initial-creator':
                $this->author = $value;
                break;

            case 'application-name':
            case 'extended-properties:application':
            case 'generator':
            case 'pdf:producer':
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

            case 'content-encoding':
                $this->encoding = $value;
                break;

            case 'x-tika:content':
                $this->content = $value;
                break;
        }

        return $this;
    }
}
