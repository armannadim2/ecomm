<?php

namespace App\Domain\Catalog\Listeners;

use Illuminate\Support\Facades\Log;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

/**
 * Removes EXIF/metadata (GPS position, phone model, date…) from every uploaded photo by
 * re-encoding the original file, after turning it the right way up (phone photos are often
 * stored sideways with an EXIF "rotate" flag). Runs before the thumbnails are made.
 * Privacy: staff often take product photos at home with their phone.
 */
class StripImageMetadata
{
    private const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Bigger photos are left as they are rather than risk running out of memory on shared hosting. */
    private const MAX_PIXELS = 40_000_000;

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! in_array($media->mime_type, self::TYPES, true)) {
            return;
        }

        $path = $media->getPath();
        if (! is_file($path)) {
            return; // not on a local disk
        }

        try {
            [$width, $height] = getimagesize($path) ?: [0, 0];
            if ($width * $height === 0 || $width * $height > self::MAX_PIXELS) {
                return;
            }

            // loadFile() already turns the photo upright from its EXIF flag; saving with GD writes no metadata.
            Image::useImageDriver(ImageDriver::Gd)
                ->loadFile($path)
                ->quality(90)
                ->save($path);

            clearstatcache(true, $path);
            $media->forceFill(['size' => filesize($path)])->saveQuietly();
        } catch (Throwable $e) {
            // Never lose the upload because of this: log it and keep the original.
            Log::warning('Could not strip image metadata', ['media' => $media->id, 'error' => $e->getMessage()]);
        }
    }
}
