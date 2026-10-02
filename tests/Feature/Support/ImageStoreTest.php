<?php

use App\Support\Images\ImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores an upload as WebP in every width without scaling it up', function () {
    Storage::fake('public');
    $upload = UploadedFile::fake()->image('ekran.png', 1000, 600);

    $base = app(ImageStore::class)->store($upload->getRealPath(), 'projects');

    foreach (ImageStore::WIDTHS as $width) {
        Storage::disk('public')->assertExists(ImageStore::file($base, $width));
    }

    [$largestWidth] = getimagesizefromstring(Storage::disk('public')->get(ImageStore::file($base, 1600)));
    [$smallestWidth] = getimagesizefromstring(Storage::disk('public')->get(ImageStore::file($base, 480)));
    expect($largestWidth)->toBe(1000)->and($smallestWidth)->toBe(480)
        ->and(Storage::disk('public')->mimeType(ImageStore::file($base, 960)))->toBe('image/webp');
});

it('deletes every width of an image', function () {
    Storage::fake('public');
    $upload = UploadedFile::fake()->image('a.jpg', 600, 400);
    $base = app(ImageStore::class)->store($upload->getRealPath(), 'projects');

    app(ImageStore::class)->delete($base);

    expect(Storage::disk('public')->allFiles('projects'))->toBe([]);
});
