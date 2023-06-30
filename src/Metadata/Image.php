<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

use Vaites\ApacheTika\Metadata;
use Vaites\ApacheTika\Contracts\Metadata as Contract;

/**
 * Metadata class for images
 */
class Image extends Metadata
{
    /**
     * Image width in pixels
     */
    public int $width = 0;

    /**
     * Image height in pixels
     */
    public int $height = 0;

    /**
     * Lossy/Lossless
     */
    public bool $lossless = true;

    /**
     * Sets an attribute
     */
    protected function setSpecificAttribute(string $key, string $value): Contract
    {
        switch(mb_strtolower($key))
        {
            case 'compression':
            case 'compression lossless':
                $this->lossless = ($value == 'true' || $value == 'Uncompressed');
                break;

            case 'height':
            case 'image height':
            case 'tiff:imageheigth':
            case 'tiff:imagelength':
                $this->height = (int) $value;
                break;

            case 'width':
            case 'image width':
            case 'tiff:imagewidth':
                $this->width = (int) $value;
                break;

            case 'x-tika:content':
                $this->content = $value;
                break;
        }

        return $this;
    }
}
