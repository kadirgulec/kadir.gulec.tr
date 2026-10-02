<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Support\Images\ImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

describe('access', function () {
    it('keeps members out of the project screens', function () {
        $this->actingAs(User::factory()->member()->create())
            ->get(route('admin.projects.index'))
            ->assertNotFound();
    });
});

describe('create', function () {
    it('creates a project with technologies, a slug from its name and a WebP cover', function () {
        Livewire::test('pages::admin.projects.edit')
            ->set('form.name', 'Çalışan Portalı')
            ->set('form.tagline', 'İzin talepleri için bir portal.')
            ->set('form.status', ProjectStatus::Live->value)
            ->set('form.started_year', 2023)
            ->set('form.body', "## Neden?\n\nÇünkü ==gerekliydi==.")
            ->set('form.technologyNames', ['Laravel', 'Vue'])
            ->set('form.cover', UploadedFile::fake()->image('kapak.png', 1200, 750))
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::sole();
        expect($project->slug)->toBe('calisan-portali')
            ->and($project->body_html)->toContain('<mark class="marker">gerekliydi</mark>')
            ->and($project->technologies->pluck('name')->all())->toBe(['Laravel', 'Vue'])
            ->and($project->published_at)->toBeNull();
        Storage::disk('public')->assertExists(ImageStore::file($project->cover_path, 960));
    });

    it('requires a name, a tagline and a valid year', function () {
        Livewire::test('pages::admin.projects.edit')
            ->set('form.started_year', 1800)
            ->call('save')
            ->assertHasErrors(['form.name' => 'required', 'form.tagline' => 'required', 'form.started_year' => 'between'])
            ->assertSee('Ad alanı zorunlu.');
    });

    it('rejects a cover that is not an image', function () {
        Livewire::test('pages::admin.projects.edit')
            ->set('form.name', 'X')
            ->set('form.tagline', 'Y')
            ->set('form.cover', UploadedFile::fake()->create('belge.pdf', 10, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['form.cover' => 'image']);
    });
});

describe('update', function () {
    it('keeps only one featured project', function () {
        $old = Project::factory()->featured()->create();
        $project = Project::factory()->create();

        Livewire::test('pages::admin.projects.edit', ['project' => $project])
            ->set('form.is_featured', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($project->fresh()->is_featured)->toBeTrue()
            ->and($old->fresh()->is_featured)->toBeFalse();
    });

    it('deletes the files of a replaced cover', function () {
        $project = Project::factory()->create();
        $upload = UploadedFile::fake()->image('eski.png', 600, 400);
        $oldCover = app(ImageStore::class)->store($upload->getRealPath(), 'projects');
        $project->forceFill(['cover_path' => $oldCover])->save();

        Livewire::test('pages::admin.projects.edit', ['project' => $project])
            ->set('form.cover', UploadedFile::fake()->image('yeni.png', 600, 400))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing(ImageStore::file($oldCover, 960));
        Storage::disk('public')->assertExists(ImageStore::file($project->fresh()->cover_path, 960));
    });

    it('publishes, schedules and drafts through the publication date', function () {
        $project = Project::factory()->draft()->create();
        $component = Livewire::test('pages::admin.projects.edit', ['project' => $project]);

        $component->call('publishNow')->call('save');
        expect($project->fresh()->isPublished())->toBeTrue();

        $component->set('form.published_at', now()->addWeek()->format('Y-m-d\TH:i'))->call('save');
        expect($project->fresh()->publicationState()->value)->toBe('scheduled');

        $component->set('form.published_at', '')->call('save');
        expect($project->fresh()->published_at)->toBeNull();
    });
});

describe('gallery and devlog', function () {
    it('adds, captions, reorders and deletes gallery images', function () {
        $project = Project::factory()->create();

        $component = Livewire::test('pages::admin.projects.edit', ['project' => $project])
            ->set('newImages', [UploadedFile::fake()->image('a.png', 400, 300), UploadedFile::fake()->image('b.png', 400, 300)])
            ->assertHasNoErrors();

        [$first, $second] = $project->images()->get()->all();

        $component->set("captions.{$first->id}", 'giriş ekranı')->call('saveCaption', $first->id)
            ->call('sortImage', $second->id, 0);

        expect($first->fresh()->caption)->toBe('giriş ekranı')
            ->and($project->images()->pluck('id')->all())->toBe([$second->id, $first->id]);

        $component->call('deleteImage', $first->id);
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing(ImageStore::file($first->path, 960));
    });

    it('adds, edits and deletes devlog entries', function () {
        $project = Project::factory()->create();

        $component = Livewire::test('pages::admin.projects.edit', ['project' => $project])
            ->set('logDate', '2026-09-01')
            ->set('logBody', 'İlk **sürüm**.')
            ->call('saveLog')
            ->assertHasNoErrors();

        $entry = $project->devlog()->sole();
        expect($entry->body_html)->toContain('<strong>sürüm</strong>');

        $component->call('editLog', $entry->id)->set('logBody', 'Düzeltildi.')->call('saveLog');
        expect($entry->fresh()->body)->toBe('Düzeltildi.');

        $component->call('deleteLog', $entry->id);
        $this->assertModelMissing($entry);
    });

    it('rejects devlog entries dated in the future', function () {
        Livewire::test('pages::admin.projects.edit', ['project' => Project::factory()->create()])
            ->set('logDate', now()->addDay()->toDateString())
            ->set('logBody', 'Gelecek.')
            ->call('saveLog')
            ->assertHasErrors(['logDate' => 'before_or_equal']);
    });
});

describe('order and delete', function () {
    it('moves a project to a new position', function () {
        $a = Project::factory()->create(['sort_order' => 0]);
        $b = Project::factory()->create(['sort_order' => 1]);
        $c = Project::factory()->create(['sort_order' => 2]);

        Livewire::test('pages::admin.projects.index')->call('sort', $c->id, 0);

        expect(Project::query()->orderBy('sort_order')->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
    });

    it('deletes a project with its images and devlog', function () {
        $project = Project::factory()->create();
        $upload = UploadedFile::fake()->image('a.png', 300, 200);
        $image = $project->images()->create(['path' => app(ImageStore::class)->store($upload->getRealPath(), 'projects')]);
        $entry = $project->devlog()->create(['date' => '2026-01-01', 'body' => 'x']);

        Livewire::test('pages::admin.projects.edit', ['project' => $project])
            ->call('delete')
            ->assertRedirect(route('admin.projects.index'));

        $this->assertModelMissing($project);
        $this->assertModelMissing($image);
        $this->assertModelMissing($entry);
        Storage::disk('public')->assertMissing(ImageStore::file($image->path, 480));
    });
});
