<?php

namespace App\Support\Backups;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Backups made and restored from the admin panel. A backup is one zip:
 *
 *   database.sql   mysqldump of the whole database
 *   storage/…      every uploaded file (storage/app/public)
 *   manifest.json  when, from which code, which migrations, how much content
 *
 * Archives live on the private "local" disk (never on the public one).
 * A restore only accepts an archive whose migrations match the code, and
 * takes a backup of the current state first.
 */
class BackupManager
{
    public const DIRECTORY = 'backups';

    private const COUNTED_TABLES = ['posts', 'projects', 'watchables', 'goals', 'users', 'comments'];

    /**
     * @param  'manual'|'before-restore'  $reason
     */
    public function create(string $reason = 'manual'): string
    {
        $name = 'yedek-'.now()->format('Y-m-d-His').($reason === 'before-restore' ? '-geri-yukleme-oncesi' : '').'.zip';
        $workDir = storage_path('app/backup-work/'.Str::random(12));
        File::ensureDirectoryExists($workDir);

        try {
            $sqlFile = $workDir.'/database.sql';
            $this->dump($sqlFile);

            $zipPath = $workDir.'/'.$name;
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Could not create the backup archive.');
            }

            $zip->addFile($sqlFile, 'database.sql');

            foreach (File::allFiles(storage_path('app/public')) as $file) {
                $zip->addFile($file->getPathname(), 'storage/'.str_replace('\\', '/', $file->getRelativePathname()));
            }

            $zip->addFromString('manifest.json', (string) json_encode($this->manifest($reason), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->close();

            $stream = fopen($zipPath, 'r');

            if ($stream === false) {
                throw new RuntimeException('Could not read the backup archive.');
            }

            Storage::disk('local')->writeStream(self::DIRECTORY.'/'.$name, $stream);
            fclose($stream);
        } finally {
            File::deleteDirectory($workDir);
        }

        return $name;
    }

    /**
     * @return Collection<int, array{name: string, size: int, createdAt: Carbon}>
     */
    public function all(): Collection
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::DIRECTORY))
            ->filter(fn (string $path): bool => str_ends_with($path, '.zip'))
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => $disk->size($path),
                'createdAt' => Carbon::createFromTimestamp($disk->lastModified($path)),
            ])
            ->sortByDesc('createdAt')
            ->values();
    }

    public function path(string $name): string
    {
        if (! preg_match('/^yedek-[0-9a-z-]+\.zip$/', $name) || ! Storage::disk('local')->exists(self::DIRECTORY.'/'.$name)) {
            throw new RuntimeException('Unknown backup.');
        }

        return Storage::disk('local')->path(self::DIRECTORY.'/'.$name);
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }

    /**
     * Reads and checks an archive before a restore.
     *
     * @return array{createdAt: string, app: ?string, counts: array<string, int>, compatible: bool, problems: list<string>}
     */
    public function inspect(string $zipPath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            return ['createdAt' => '', 'app' => null, 'counts' => [], 'compatible' => false, 'problems' => ['Dosya bir zip arşivi değil.']];
        }

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $hasDump = $zip->locateName('database.sql') !== false;
        $problems = [];

        foreach (range(0, $zip->numFiles - 1) as $index) {
            if (! $this->isSafeEntry((string) $zip->getNameIndex($index))) {
                $problems[] = 'Arşivde izin verilmeyen bir dosya yolu var.';
                break;
            }
        }

        $zip->close();

        if (! is_array($manifest) || ($manifest['format'] ?? null) !== 1) {
            $problems[] = 'manifest.json yok ya da tanınmıyor; bu bir kadir.gulec.tr yedeği değil.';
        }

        if (! $hasDump) {
            $problems[] = 'Arşivde database.sql yok.';
        }

        if (is_array($manifest) && isset($manifest['migrations'])) {
            $current = $this->migrations();
            $missing = array_diff($manifest['migrations'], $current);
            $newer = array_diff($current, $manifest['migrations']);

            if ($missing !== []) {
                $problems[] = 'Bu yedek daha yeni bir sürümden: kodda olmayan '.count($missing).' migration içeriyor. Önce kodu güncelle.';
            }

            if ($newer !== []) {
                $problems[] = 'Bu yedek daha eski bir sürümden: '.count($newer).' migration eksik. Yedeği bu sürümle alıp tekrar dene.';
            }
        }

        return [
            'createdAt' => (string) ($manifest['created_at'] ?? ''),
            'app' => $manifest['app'] ?? null,
            'counts' => is_array($manifest['counts'] ?? null) ? $manifest['counts'] : [],
            'compatible' => $problems === [],
            'problems' => array_values(array_unique($problems)),
        ];
    }

    /**
     * Replaces the database and the uploaded files with an archive's.
     * Returns the name of the backup taken right before.
     */
    public function restore(string $zipPath): string
    {
        $check = $this->inspect($zipPath);

        if (! $check['compatible']) {
            throw new RuntimeException(implode(' ', $check['problems']));
        }

        $safetyBackup = $this->create('before-restore');
        $workDir = storage_path('app/backup-work/'.Str::random(12));
        File::ensureDirectoryExists($workDir);

        Artisan::call('down', ['--retry' => 60]);

        try {
            $zip = new ZipArchive;
            $zip->open($zipPath);
            $zip->extractTo($workDir);
            $zip->close();

            $this->import($workDir.'/database.sql');

            $public = storage_path('app/public');
            $gitignore = File::exists($public.'/.gitignore') ? File::get($public.'/.gitignore') : null;
            File::cleanDirectory($public);

            if ($gitignore !== null) {
                File::put($public.'/.gitignore', $gitignore);
            }

            if (File::isDirectory($workDir.'/storage')) {
                File::copyDirectory($workDir.'/storage', $public);
            }

            Artisan::call('optimize:clear');
        } finally {
            File::deleteDirectory($workDir);
            Artisan::call('up');
        }

        return $safetyBackup;
    }

    /**
     * @return array{format: int, created_at: string, reason: string, app: ?string, migrations: list<string>, counts: array<string, int>}
     */
    private function manifest(string $reason): array
    {
        return [
            'format' => 1,
            'created_at' => now()->toIso8601String(),
            'reason' => $reason,
            'app' => $this->commit(),
            'migrations' => $this->migrations(),
            'counts' => collect(self::COUNTED_TABLES)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function migrations(): array
    {
        return array_values(array_map('strval', DB::table('migrations')->orderBy('id')->pluck('migration')->all()));
    }

    private function commit(): ?string
    {
        $process = new Process(['git', 'rev-parse', '--short', 'HEAD'], base_path());
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : null;
    }

    private function isSafeEntry(string $name): bool
    {
        if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        return in_array($name, ['database.sql', 'manifest.json'], true) || str_starts_with($name, 'storage/');
    }

    private function dump(string $file): void
    {
        $connection = $this->connection();

        $process = new Process([
            'mysqldump',
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--add-drop-table',
            '--default-character-set=utf8mb4',
            '--result-file='.$file,
            $connection['database'],
        ], env: ['MYSQL_PWD' => $connection['password']], timeout: 600);

        $process->mustRun();
    }

    private function import(string $file): void
    {
        $connection = $this->connection();

        $process = new Process([
            'mysql',
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--default-character-set=utf8mb4',
            $connection['database'],
        ], env: ['MYSQL_PWD' => $connection['password']], input: fopen($file, 'r'), timeout: 600);

        $process->mustRun();
    }

    /**
     * @return array{host: string, port: string, username: string, password: string, database: string}
     */
    private function connection(): array
    {
        $config = config('database.connections.'.config('database.default'));

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backups need a MySQL database.');
        }

        return [
            'host' => (string) $config['host'],
            'port' => (string) $config['port'],
            'username' => (string) $config['username'],
            'password' => (string) $config['password'],
            'database' => (string) $config['database'],
        ];
    }
}
