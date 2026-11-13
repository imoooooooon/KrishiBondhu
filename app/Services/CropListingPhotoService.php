<?php

namespace App\Services;

use App\Exceptions\UnusableCropPhotoException;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Turns an uploaded crop photo into two safe JPEG files (F1 spec §14), using PHP's built-in GD extension.
 *
 * Why re-encode instead of saving the upload as it is:
 * - the browser's file type and name cannot be trusted; decoding with GD proves it is a real image;
 * - re-encoding drops all metadata (EXIF, GPS location of the farm, camera serial numbers);
 * - buyers on mobile data download a small thumbnail and a 1600 px display image, never a 10 MB original.
 *
 * Files go to the PRIVATE disk (storage/app/private), never public/. They are only sent through
 * CropListingPhotoController, which checks that the listing is still published (or that the viewer owns it).
 */
class CropListingPhotoService
{
    public const DISK = 'local';

    public const DIRECTORY = 'crop-listing-photos';

    /** Limits checked by the Form Requests too (in kilobytes there, because Laravel's file rule uses KB). */
    public const MAX_KILOBYTES = 5120;

    public const MAX_SIDE = 6000;

    public const MAX_PIXELS = 12_000_000;

    public const DISPLAY_EDGE = 1600;

    public const THUMB_EDGE = 480;

    private const DISPLAY_QUALITY = 82;

    private const THUMB_QUALITY = 75;

    /**
     * Decode, straighten, resize and save one uploaded photo.
     *
     * @return array{display_path: string, thumbnail_path: string, display_width: int, display_height: int, thumbnail_width: int, thumbnail_height: int}
     *
     * @throws UnusableCropPhotoException when the file is not a usable JPEG/PNG
     * @throws RuntimeException when the files cannot be written (disk full, permissions)
     */
    public function store(UploadedFile $file): array
    {
        $source = $this->decode($file->getRealPath());

        $written = [];
        try {
            $base = self::DIRECTORY.'/'.bin2hex(random_bytes(16)); // unpredictable, never from the request
            $display = $this->writeVariant($source, $base.'-display.jpg', self::DISPLAY_EDGE, self::DISPLAY_QUALITY);
            $written[] = $display['path'];
            $thumb = $this->writeVariant($source, $base.'-thumb.jpg', self::THUMB_EDGE, self::THUMB_QUALITY);
            $written[] = $thumb['path'];
        } catch (Throwable $e) {
            $this->delete($written); // do not leave half a photo behind

            throw $e;
        }

        return [
            'display_path' => $display['path'],
            'thumbnail_path' => $thumb['path'],
            'display_width' => $display['width'],
            'display_height' => $display['height'],
            'thumbnail_width' => $thumb['width'],
            'thumbnail_height' => $thumb['height'],
        ];
    }

    /**
     * Delete files (cleanup after a failed save, or old photos after a successful edit).
     * A failure is logged, not thrown: the database is already correct, and
     * `php artisan crop-listings:prune-photos` removes leftover files later.
     *
     * @param  list<string>  $paths
     */
    public function delete(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        try {
            if (! Storage::disk(self::DISK)->delete($paths)) {
                Log::warning('Crop listing photo cleanup could not delete some files.', ['count' => count($paths)]);
            }
        } catch (Throwable $e) {
            Log::warning('Crop listing photo cleanup failed.', ['count' => count($paths), 'error' => $e->getMessage()]);
        }
    }

    /** Is the file still on disk? Used before republishing a listing. */
    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    /** Read a stored variant, or null when the file has gone missing. */
    public function read(string $path): ?string
    {
        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    /** Check the real content and size, then decode into a GD image with the camera rotation applied. */
    private function decode(string $path): GdImage
    {
        // Reads only the header: cheap, and lets us refuse huge images before decoding them into memory.
        $info = @getimagesize($path);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new UnusableCropPhotoException(UnusableCropPhotoException::NOT_AN_IMAGE);
        }

        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE || $width * $height > self::MAX_PIXELS) {
            throw new UnusableCropPhotoException(UnusableCropPhotoException::TOO_MANY_PIXELS);
        }

        // By default GD accepts a truncated JPEG and fills the missing part with grey. Refuse it instead.
        $previous = ini_set('gd.jpeg_ignore_warning', '0');
        try {
            // "@": GD reports broken files as PHP warnings; we check the return value instead.
            $image = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($path) : @imagecreatefrompng($path);
        } finally {
            if ($previous !== false) {
                ini_set('gd.jpeg_ignore_warning', $previous);
            }
        }

        if (! $image instanceof GdImage) {
            throw new UnusableCropPhotoException(UnusableCropPhotoException::CANNOT_DECODE);
        }

        return $info[2] === IMAGETYPE_JPEG ? $this->applyOrientation($image, $this->jpegOrientation($path)) : $image;
    }

    /**
     * Resize (never enlarge) onto a white background and save as JPEG.
     * White background: a transparent PNG would otherwise turn black when saved as JPEG.
     *
     * @return array{path: string, width: int, height: int}
     */
    private function writeVariant(GdImage $source, string $path, int $longestEdge, int $quality): array
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $longestEdge / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));

        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        imageinterlace($canvas, true); // progressive JPEG: a blurry preview appears early on slow networks

        ob_start();
        $encoded = imagejpeg($canvas, null, $quality);
        $bytes = (string) ob_get_clean();

        // imagejpeg() returning true is not proof of a usable file: check the bytes really are our JPEG.
        $check = $bytes !== '' ? @getimagesizefromstring($bytes) : false;
        if (! $encoded || $check === false || $check[2] !== IMAGETYPE_JPEG || $check[0] !== $width || $check[1] !== $height) {
            throw new RuntimeException('Crop photo could not be encoded as JPEG.');
        }

        // put() returns false instead of throwing on the default disk config, so check it.
        if (Storage::disk(self::DISK)->put($path, $bytes) !== true) {
            throw new RuntimeException('Crop photo could not be saved to storage.');
        }

        return ['path' => $path, 'width' => $width, 'height' => $height];
    }

    /**
     * Phone cameras save photos sideways and store "rotate me" in EXIF tag 0x0112 (Orientation).
     * We remove EXIF, so the rotation must be applied to the pixels first or the crop would appear sideways.
     * Values: 1 normal, 2 mirrored, 3 upside down, 4 mirrored + upside down, 5–8 the 90° turns.
     */
    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        // imagerotate() turns counter-clockwise, so 270 here is a quarter turn clockwise.
        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated instanceof GdImage) {
                $image = $rotated;
            }
        }
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    /**
     * Read the EXIF Orientation value from a JPEG without the exif extension (not installed on every
     * computer or host). Walks the JPEG segments to the APP1 "Exif" block and reads tag 0x0112 from
     * the first image directory. Anything unexpected means "1" (leave the photo as it is).
     */
    private function jpegOrientation(string $path): int
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return 1;
        }

        try {
            if (fread($handle, 2) !== "\xFF\xD8") {
                return 1; // not a JPEG start-of-image marker
            }

            for ($segment = 0; $segment < 32; $segment++) {
                $header = fread($handle, 4);
                if ($header === false || strlen($header) < 4 || $header[0] !== "\xFF") {
                    return 1;
                }
                $marker = ord($header[1]);
                $length = unpack('n', substr($header, 2, 2))[1];
                if ($marker === 0xDA || $marker === 0xD9 || $length < 2) {
                    return 1; // image data starts: no EXIF before it
                }
                if ($marker === 0xE1) {
                    $data = fread($handle, $length - 2);

                    return is_string($data) && str_starts_with($data, "Exif\0\0")
                        ? $this->orientationFromTiff(substr($data, 6))
                        : 1;
                }
                fseek($handle, $length - 2, SEEK_CUR);
            }

            return 1;
        } finally {
            fclose($handle);
        }
    }

    private function orientationFromTiff(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }

        $order = substr($tiff, 0, 2);
        if ($order !== 'II' && $order !== 'MM') {
            return 1;
        }
        $short = $order === 'II' ? 'v' : 'n'; // little-endian (Intel) or big-endian (Motorola)
        $long = $order === 'II' ? 'V' : 'N';

        $offset = unpack($long, substr($tiff, 4, 4))[1];
        if ($offset + 2 > strlen($tiff)) {
            return 1;
        }
        $entries = unpack($short, substr($tiff, $offset, 2))[1];

        for ($i = 0; $i < min($entries, 256); $i++) {
            $entry = $offset + 2 + $i * 12;
            if ($entry + 12 > strlen($tiff)) {
                return 1;
            }
            if (unpack($short, substr($tiff, $entry, 2))[1] === 0x0112) {
                $value = unpack($short, substr($tiff, $entry + 8, 2))[1];

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }
}
