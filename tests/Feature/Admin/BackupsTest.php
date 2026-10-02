<?php

use App\Models\User;
use App\Support\Backups\BackupManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

/**
 * @param  array<string, string>  $entries
 */
function zipWith(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'backup').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return $path;
}

function manifestFor(array $migrations): string
{
    return json_encode(['format' => 1, 'created_at' => now()->toIso8601String(), 'app' => 'abc123', 'migrations' => $migrations, 'counts' => ['posts' => 3]]);
}

it('makes a zip with the database dump, the uploads and a manifest', function () {
    $name = app(BackupManager::class)->create();

    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path('backups/'.$name));
    $manifest = json_decode($zip->getFromName('manifest.json'), true);

    expect($zip->locateName('database.sql'))->not->toBeFalse()
        ->and($zip->getFromName('database.sql'))->toContain('CREATE TABLE `posts`')
        ->and($manifest['format'])->toBe(1)
        ->and($manifest['migrations'])->toContain('2026_10_02_150013_sync_permissions_and_system_roles')
        ->and($manifest['counts'])->toHaveKey('users');
});

it('accepts an archive made with the same migrations', function () {
    $migrations = DB::table('migrations')->orderBy('id')->pluck('migration')->all();

    $check = app(BackupManager::class)->inspect(zipWith(['manifest.json' => manifestFor($migrations), 'database.sql' => '--', 'storage/posts/a.webp' => 'x']));

    expect($check['compatible'])->toBeTrue()->and($check['counts']['posts'])->toBe(3);
});

it('refuses archives from another version, without a dump or with unsafe paths', function (Closure $entries, string $problem) {
    $check = app(BackupManager::class)->inspect(zipWith($entries()));

    expect($check['compatible'])->toBeFalse()->and(implode(' ', $check['problems']))->toContain($problem);
})->with([
    'newer code' => [fn () => ['manifest.json' => manifestFor([...DB::table('migrations')->pluck('migration')->all(), '2099_01_01_000000_future']), 'database.sql' => '--'], 'daha yeni bir sürümden'],
    'older code' => [fn () => ['manifest.json' => manifestFor(['0001_01_01_000000_create_users_table']), 'database.sql' => '--'], 'daha eski bir sürümden'],
    'no dump' => [fn () => ['manifest.json' => manifestFor(DB::table('migrations')->pluck('migration')->all())], 'database.sql yok'],
    'no manifest' => [fn () => ['database.sql' => '--'], 'manifest.json yok'],
    'zip slip' => [fn () => ['manifest.json' => manifestFor(DB::table('migrations')->pluck('migration')->all()), 'database.sql' => '--', '../../evil.php' => '<?php'], 'izin verilmeyen'],
]);

it('lets only the admin download a backup, through a signed link', function () {
    $name = app(BackupManager::class)->create();

    $this->get(URL::temporarySignedRoute('admin.backups.download', now()->addMinutes(5), ['name' => $name]))->assertOk()->assertDownload($name);
    $this->get('/admin/yedekler/'.$name)->assertForbidden();

    $this->actingAs(User::factory()->member()->create());
    $this->get(URL::temporarySignedRoute('admin.backups.download', now()->addMinutes(5), ['name' => $name]))->assertNotFound();
});

it('creates a backup from the page', function () {
    Livewire::test('pages::admin.backups.index')->call('create')->assertHasNoErrors();

    expect(app(BackupManager::class)->all())->toHaveCount(1);
});

describe('restore', function () {
    beforeEach(function () {
        $this->migrations = DB::table('migrations')->orderBy('id')->pluck('migration')->all();
        $this->upload = UploadedFile::fake()->createWithContent('yedek.zip', file_get_contents(zipWith(['manifest.json' => manifestFor($this->migrations), 'database.sql' => '--'])));
    });

    it('asks for the password and the exact phrase before restoring', function () {
        $manager = Mockery::mock(BackupManager::class)->makePartial();
        $manager->shouldNotReceive('restore');
        $this->app->instance(BackupManager::class, $manager);

        Livewire::test('pages::admin.backups.index')
            ->set('archive', $this->upload)
            ->assertSee('3 yazı')
            ->set('password', 'yanlış')
            ->set('confirmation', 'geri yükle')
            ->call('restore')
            ->assertHasErrors(['password' => 'current_password', 'confirmation' => 'in']);
    });

    it('restores once both are right', function () {
        $manager = Mockery::mock(BackupManager::class)->makePartial();
        $manager->shouldReceive('restore')->once()->andReturn('yedek-oncesi.zip');
        $this->app->instance(BackupManager::class, $manager);

        Livewire::test('pages::admin.backups.index')
            ->set('archive', $this->upload)
            ->set('password', 'password')
            ->set('confirmation', 'GERİ YÜKLE')
            ->call('restore')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.backups.index'));
    });

    it('never restores an incompatible archive', function () {
        $upload = UploadedFile::fake()->createWithContent('eski.zip', file_get_contents(zipWith(['database.sql' => '--'])));

        Livewire::test('pages::admin.backups.index')
            ->set('archive', $upload)
            ->assertSee('manifest.json yok')
            ->set('password', 'password')
            ->set('confirmation', 'GERİ YÜKLE')
            ->call('restore')
            ->assertStatus(422);
    });
});
