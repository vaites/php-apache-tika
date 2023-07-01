<?php declare(strict_types=1);

namespace Vaites\ApacheTika;

/**
 * Abstract MIME type
 */
enum MIME
{
    case BOOK;
    case DOCUMENT;
    case IMAGE;
    case TEXT;

    /**
     * Get the entity class for the MIME type
     */
    public function entity(): string
    {
        return match($this)
        {
            self::BOOK     => Entities\Book::class,
            self::DOCUMENT => Entities\Document::class,
            self::IMAGE    => Entities\Image::class,
            self::TEXT     => Entities\Text::class
        };
    }

    /**
     * Get the metadata class for the MIME type
     */
    public function metadata(): string
    {
        return match($this)
        {
            self::BOOK     => Metadata\Book::class,
            self::DOCUMENT => Metadata\Document::class,
            self::IMAGE    => Metadata\Image::class,
            self::TEXT     => Metadata\Text::class
        };
    }

    /**
     * Guess the MIME type from a string
     */
    public static function guess(string $mime): self
    {
        return match(true)
        {
            $mime === 'application/epub+zip'    => self::BOOK,
            str_starts_with($mime, 'image/')    => self::IMAGE,
            $mime === 'text/plain'              => self::TEXT,
            default                             => self::DOCUMENT
        };
    }
}