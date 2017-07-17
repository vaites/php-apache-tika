<?php

namespace Vaites\ApacheTika\Metadata;

/**
 * Metadata class for images
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
class ImageMetadata extends Metadata
{
    /**
     * Image width in pixels
     *
     * @var int
     */
    public $width = 0;

    /**
     * Image height in pixels
     *
     * @var int
     */
    public $height = 0;

    /**
     * Lossy/Lossless.
     *
     * @var bool
     */
    public $lossless = true;

    /**
     * Sets an attribute
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    protected function setAttribute($key, $value)
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

            default:
                return false;
        }

        return true;
    }
}
