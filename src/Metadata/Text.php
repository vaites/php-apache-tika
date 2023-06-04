<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

use Vaites\ApacheTika\Metadata;
use Vaites\ApacheTika\Contracts\Metadata as Contract;

/**
 * Metadata class for text files
 */
class Text extends Metadata
{
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
     * Number of words
     */
    public int $words = 0;

    /**
     * Sets an attribute
     *
     * @throws  \Exception
     */
    protected function setSpecificAttribute(string $key, string $value): Contract
    {
        switch(mb_strtolower($key))
        {
            case 'language':
                $this->language = mb_substr($value, 0, 2);
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
