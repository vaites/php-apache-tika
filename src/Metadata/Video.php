<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Metadata;

use Vaites\ApacheTika\Metadata;
use Vaites\ApacheTika\Contracts\Metadata as Contract;

/**
 * Metadata class for videos
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
class Video extends Metadata
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
     * Duration in seconds
     */
    public int $duration = 0;

    /**
     * Sets an attribute
     */
    protected function setSpecificAttribute(string $key, string $value): Contract
    {
        switch(mb_strtolower($key))
        {
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

            case 'duration':
            case 'xmpdm:duration':
                $this->duration = (int) $value;
                break;
        }

        return $this;
    }
}
