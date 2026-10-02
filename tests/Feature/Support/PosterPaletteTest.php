<?php

use App\Support\Images\PosterPalette;

it('takes the most present vivid color of an image as the accent', function () {
    $image = imagecreatetruecolor(40, 40);
    imagefill($image, 0, 0, imagecolorallocate($image, 20, 20, 20));
    imagefilledrectangle($image, 0, 0, 39, 25, imagecolorallocate($image, 200, 40, 60));
    $path = tempnam(sys_get_temp_dir(), 'poster').'.png';
    imagepng($image, $path);

    $palette = app(PosterPalette::class)->fromFile($path);

    [$r, $g, $b] = sscanf($palette['accent'], '#%02x%02x%02x');
    expect($r)->toBeGreaterThan(150)->and($g)->toBeLessThan(80)->and($b)->toBeLessThan(100)
        ->and($palette['colors'][1])->toBe($palette['accent']);
});

it('falls back to the section color for a grey image', function () {
    $image = imagecreatetruecolor(20, 20);
    imagefill($image, 0, 0, imagecolorallocate($image, 128, 128, 128));
    $path = tempnam(sys_get_temp_dir(), 'poster').'.png';
    imagepng($image, $path);

    expect(app(PosterPalette::class)->fromFile($path)['accent'])->toBe(PosterPalette::FALLBACK_ACCENT);
});
