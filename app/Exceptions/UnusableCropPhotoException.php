<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An uploaded file passed the basic checks but cannot be used as a crop photo: it is not really a
 * JPEG/PNG, it is too large in pixels, or it is broken and cannot be decoded. Thrown by
 * CropListingPhotoService; CropListingService turns it into a normal form error for that photo.
 */
class UnusableCropPhotoException extends RuntimeException
{
    public const NOT_AN_IMAGE = 'not_an_image';

    public const TOO_MANY_PIXELS = 'too_many_pixels';

    public const CANNOT_DECODE = 'cannot_decode';

    public function __construct(public readonly string $reason)
    {
        parent::__construct('Unusable crop photo: '.$reason);
    }

    /** A message the farmer can act on, in the page language. */
    public function userMessage(): string
    {
        return match ($this->reason) {
            self::TOO_MANY_PIXELS => __('This photo is too large in pixels. Use a photo of at most 6000 pixels on each side and 12 megapixels.'),
            self::CANNOT_DECODE => __('This photo is damaged or incomplete and could not be opened. Take or export it again as JPG or PNG.'),
            default => __('This file is not a JPG or PNG photo. Export it as JPG or PNG and try again.'),
        };
    }
}
