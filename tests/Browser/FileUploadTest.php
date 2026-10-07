<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/*
 * The admin upload controls (x-admin.file-upload, the image button of
 * x-admin.markdown) in a real browser: the button opens the file picker, and
 * picked or dropped files start a Livewire upload into the right property.
 *
 * The tests stop where Livewire takes over. Pest's test server keeps the
 * multipart field "files[]" flat, so Livewire's temporary upload endpoint
 * never sees the file; storing an upload is covered by the feature tests
 * (Tests\Feature\Admin\ProjectsTest), which set the property directly.
 */

/**
 * Records which file input the page asks the browser to open, instead of
 * opening a native dialog that a headless browser cannot answer, and every
 * Livewire upload that starts: its property and file names.
 */
const RECORD_UPLOADS = <<<'JS'
    window.openedPicker = null;
    window.startedUploads = [];
    HTMLInputElement.prototype.click = function () {
        if (this.type === 'file') { window.openedPicker = { accept: this.accept, multiple: this.multiple }; }
    };
    document.addEventListener('livewire-upload-start', (event) => {
        window.startedUploads.push({ property: event.detail.property, files: [...event.target.files].map(file => file.name) });
    });
JS;

/** The upload field (label, drop zone, input) whose text contains the given marker. */
function uploadField(string $text): string
{
    return '[data-upload-field]:has-text("'.$text.'")';
}

/**
 * Drops files onto the drop zone of the field containing the given text. The
 * content is a small PNG drawn on a canvas; name and type can pretend otherwise.
 *
 * @param  list<array{0: string, 1: string}>  $files  name and MIME type of each file
 */
function dropFiles(string $fieldText, array $files): string
{
    $fieldText = json_encode($fieldText);
    $files = json_encode($files);

    return <<<JS
        (async () => {
            const canvas = Object.assign(document.createElement('canvas'), { width: 40, height: 30 });
            canvas.getContext('2d').fillRect(0, 0, 40, 30);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            const transfer = new DataTransfer();
            for (const [name, type] of {$files}) { transfer.items.add(new File([blob], name, { type })); }
            const field = [...document.querySelectorAll('[data-upload-field]')].find(field => field.textContent.includes({$fieldText}));
            const zone = field.querySelector('[data-upload-zone]');
            for (const type of ['dragenter', 'dragover', 'drop']) {
                zone.dispatchEvent(new DragEvent(type, { bubbles: true, cancelable: true, dataTransfer: transfer }));
            }
            return true;
        })()
        JS;
}

/**
 * The uploads started so far, after giving Livewire a moment: it starts an
 * upload a tick after the input changes.
 */
const STARTED_UPLOADS = <<<'JS'
    new Promise(resolve => setTimeout(() => resolve(window.startedUploads), 300))
JS;

const IMAGE_TYPES = 'image/jpeg,image/png,image/webp,image/avif,image/gif';

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->project = Project::factory()->create();
});

it('opens the file picker for the cover, limited to one image', function () {
    $page = visit(route('admin.projects.edit', $this->project));
    $page->script(RECORD_UPLOADS);

    $page->click(uploadField('tarayıcı çerçevesinde').' button:has-text("dosya seç")');

    expect($page->script('window.openedPicker'))->toBe(['accept' => IMAGE_TYPES, 'multiple' => false]);
});

it('uploads a cover picked in the file picker', function () {
    $cover = UploadedFile::fake()->image('cover.png', 1200, 750);

    $page = visit(route('admin.projects.edit', $this->project));
    $page->script(RECORD_UPLOADS);

    $page->attach(uploadField('tarayıcı çerçevesinde').' input[type=file]', $cover->getRealPath());

    expect($page->script(STARTED_UPLOADS))->toBe([['property' => 'form.cover', 'files' => [basename($cover->getRealPath())]]]);
});

it('uploads only the first of several files dropped onto the cover', function () {
    $page = visit(route('admin.projects.edit', $this->project));
    $page->script(RECORD_UPLOADS);

    $page->script(dropFiles('tarayıcı çerçevesinde', [['a.png', 'image/png'], ['b.png', 'image/png']]));

    expect($page->script(STARTED_UPLOADS))->toBe([['property' => 'form.cover', 'files' => ['a.png']]]);
});

it('uploads every image dropped onto the gallery and skips other files', function () {
    $page = visit(route('admin.projects.edit', $this->project));
    $page->script(RECORD_UPLOADS);

    $page->script(dropFiles('polaroid', [['a.png', 'image/png'], ['notes.txt', 'text/plain'], ['b.webp', 'image/webp']]));

    expect($page->script(STARTED_UPLOADS))->toBe([['property' => 'newImages', 'files' => ['a.png', 'b.webp']]]);
});

it('starts no upload when nothing dropped is accepted', function () {
    $page = visit(route('admin.projects.edit', $this->project));
    $page->script(RECORD_UPLOADS);

    $page->script(dropFiles('polaroid', [['notes.txt', 'text/plain']]));

    expect($page->script(STARTED_UPLOADS))->toBe([]);
});

it('opens the file picker from the image button of the post editor and uploads the pick', function () {
    $image = UploadedFile::fake()->image('inline.png', 800, 500);

    $page = visit(route('admin.posts.create'));
    $page->script(RECORD_UPLOADS);

    $page->click('button[aria-label="Görsel ekle"]');
    $page->attach('input[x-ref="imageInput"]', $image->getRealPath());

    expect($page->script('window.openedPicker'))->toBe(['accept' => IMAGE_TYPES, 'multiple' => false])
        ->and($page->script(STARTED_UPLOADS))->toBe([['property' => 'bodyImage', 'files' => [basename($image->getRealPath())]]]);
});
