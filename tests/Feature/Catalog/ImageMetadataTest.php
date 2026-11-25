<?php

use App\Domain\Catalog\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A 40×20 JPEG with an EXIF block: camera make "Tel" and orientation 6
 * ("rotate 90° clockwise to display"), like a photo taken with a phone held upright.
 */
function jpegWithExif(): string
{
    $image = imagecreatetruecolor(40, 20);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    // TIFF header (little endian) + 1 IFD with 2 entries: Make (ASCII, inline) and Orientation (SHORT).
    $ifd = pack('v', 2)
        .pack('vvV', 0x010F, 2, 4).'Tel'."\0"
        .pack('vvVvv', 0x0112, 3, 1, 6, 0)
        .pack('V', 0);
    $tiff = 'II'.pack('v', 42).pack('V', 8).$ifd;
    $app1 = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
}

it('removes EXIF data from uploaded photos and turns them the right way up', function () {
    Storage::fake('public');
    $file = tempnam(sys_get_temp_dir(), 'exif').'.jpg';
    file_put_contents($file, jpegWithExif());
    expect(exif_read_data($file))->toHaveKey('Make'); // the test file really has EXIF

    $product = Product::factory()->create();
    $media = $product->addMedia(new UploadedFile($file, 'foto.jpg', 'image/jpeg', null, true))
        ->toMediaCollection(Product::IMAGES);

    $stored = $media->getPath();
    expect(@exif_read_data($stored) ?: [])->not->toHaveKey('Make')
        ->and(array_slice(getimagesize($stored), 0, 2))->toBe([20, 40]) // rotated upright
        ->and($media->fresh()->size)->toBe(filesize($stored));
});

it('keeps the size of photos that have no rotation flag', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $media = $product->addMedia(UploadedFile::fake()->image('plano.png', 30, 10))->toMediaCollection(Product::IMAGES);

    expect(array_slice(getimagesize($media->getPath()), 0, 2))->toBe([30, 10]);
});
