<?php

namespace Tests\Feature\CropListings;

use App\Exceptions\UnusableCropPhotoException;
use App\Services\CropListingPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * F1 spec §14 with REAL images made by GD (not mocks): resizing, camera rotation, metadata removal,
 * transparent PNGs and broken files. No database needed.
 */
class CropListingPhotoServiceTest extends TestCase
{
    private CropListingPhotoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(CropListingPhotoService::DISK);
        $this->service = new CropListingPhotoService;
    }

    /** A real JPEG (landscape), optionally with an EXIF block carrying an Orientation tag and a fake GPS text. */
    private function jpeg(int $width, int $height, ?int $orientation = null): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 40));
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = ob_get_clean();

        if ($orientation !== null) {
            // TIFF (big-endian): header, 1 IFD entry (0x0112 SHORT = orientation), next-IFD 0, then a marker text.
            $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00"."\x00\x00\x00\x00".'GPS-SECRET-23.81N';
            $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\x00\x00".$tiff;
            $bytes = substr($bytes, 0, 2).$app1.substr($bytes, 2);
        }

        return UploadedFile::fake()->createWithContent('camera.jpg', $bytes);
    }

    private function storedSize(string $path): array
    {
        return getimagesizefromstring(Storage::disk(CropListingPhotoService::DISK)->get($path));
    }

    public function test_large_photos_are_resized_into_two_jpegs_without_enlarging_small_ones(): void
    {
        $big = $this->service->store($this->jpeg(3200, 2000));
        $this->assertSame([1600, 1000], [$big['display_width'], $big['display_height']]);
        $this->assertSame([480, 300], [$big['thumbnail_width'], $big['thumbnail_height']]);
        $this->assertSame([1600, 1000, IMAGETYPE_JPEG], array_slice($this->storedSize($big['display_path']), 0, 3));
        $this->assertSame([480, 300], array_slice($this->storedSize($big['thumbnail_path']), 0, 2));

        $small = $this->service->store($this->jpeg(300, 200));
        $this->assertSame([300, 200], [$small['display_width'], $small['display_height']]);
        $this->assertSame([300, 200], [$small['thumbnail_width'], $small['thumbnail_height']]);
    }

    public function test_paths_are_generated_and_private(): void
    {
        $stored = $this->service->store(UploadedFile::fake()->image('../../../public/evil.php.jpg', 50, 50));

        $this->assertMatchesRegularExpression('#^crop-listing-photos/[0-9a-f]{32}-display\.jpg$#', $stored['display_path']);
        $this->assertStringNotContainsString('evil', $stored['display_path'].$stored['thumbnail_path']);
    }

    public function test_sideways_camera_photos_are_turned_upright(): void
    {
        foreach ([6, 8, 5, 7] as $orientation) { // the four quarter-turn orientations
            $stored = $this->service->store($this->jpeg(400, 200, $orientation));
            $this->assertSame([200, 400], [$stored['display_width'], $stored['display_height']], "orientation {$orientation}");
        }

        $upsideDown = $this->service->store($this->jpeg(400, 200, 3));
        $this->assertSame([400, 200], [$upsideDown['display_width'], $upsideDown['display_height']]);
    }

    public function test_metadata_such_as_location_is_removed(): void
    {
        $upload = $this->jpeg(400, 200, 6);
        $this->assertStringContainsString('GPS-SECRET', file_get_contents($upload->getRealPath()));

        $stored = $this->service->store($upload);
        foreach ([$stored['display_path'], $stored['thumbnail_path']] as $path) {
            $bytes = Storage::disk(CropListingPhotoService::DISK)->get($path);
            $this->assertStringNotContainsString('GPS-SECRET', $bytes);
            $this->assertStringNotContainsString('Exif', $bytes);
        }
    }

    public function test_transparent_png_becomes_a_jpeg_on_white(): void
    {
        $image = imagecreatetruecolor(40, 40);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127)); // fully transparent
        ob_start();
        imagepng($image);
        $png = UploadedFile::fake()->createWithContent('leaf.png', ob_get_clean());

        $stored = $this->service->store($png);

        $this->assertSame(IMAGETYPE_JPEG, $this->storedSize($stored['display_path'])[2]);
        $decoded = imagecreatefromstring(Storage::disk(CropListingPhotoService::DISK)->get($stored['display_path']));
        $rgb = imagecolorsforindex($decoded, imagecolorat($decoded, 20, 20));
        $this->assertGreaterThan(245, $rgb['red'], 'transparent pixels are white, not black');
    }

    public function test_broken_and_fake_images_are_refused_without_leaving_files(): void
    {
        $whole = $this->jpeg(800, 600);
        $cases = [
            'truncated jpeg' => [substr(file_get_contents($whole->getRealPath()), 0, 1200), UnusableCropPhotoException::CANNOT_DECODE],
            'text named jpg' => ['just some text', UnusableCropPhotoException::NOT_AN_IMAGE],
            'gif' => ['GIF89a'.str_repeat("\0", 20), UnusableCropPhotoException::NOT_AN_IMAGE],
        ];

        foreach ($cases as $name => [$bytes, $reason]) {
            try {
                $this->service->store(UploadedFile::fake()->createWithContent('x.jpg', $bytes));
                $this->fail("{$name} should be refused");
            } catch (UnusableCropPhotoException $e) {
                $this->assertSame($reason, $e->reason, $name);
            }
        }

        $this->assertSame([], Storage::disk(CropListingPhotoService::DISK)->allFiles());
    }

    public function test_too_many_pixels_is_refused_before_decoding(): void
    {
        // A valid PNG header claiming 4000 x 3001 pixels (just over 12 megapixels); no pixel data is needed.
        $header = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 4000, 3001)."\x08\x02\x00\x00\x00";
        $png = $header.pack('N', crc32(substr($header, 12, 17)));

        $this->expectExceptionObject(new UnusableCropPhotoException(UnusableCropPhotoException::TOO_MANY_PIXELS));
        $this->service->store(UploadedFile::fake()->createWithContent('huge.png', $png));
    }
}
