<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Entities;

use Vaites\ApacheTika\Entity;

/**
 * Document entity
 *
 * @see \Vaites\ApacheTika\Metadata\Document
 *
 * @property-read null|string $title
 * @property-read null|string $description
 * @property-read null|string $language
 * @property-read null|string $encoding
 * @property-read null|string $generator
 * @property-read null|string $author
 * @property-read array $keywords
 * @property-read int $pages
 * @property-read int $words
 */
class Document extends Entity
{

}