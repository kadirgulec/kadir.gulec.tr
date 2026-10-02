<?php

namespace Database\Seeders;

use App\Enums\ToolboxGroup;
use App\Models\Project;
use App\Models\Technology;
use App\Support\Images\ImageStore;
use Illuminate\Database\Seeder;

/**
 * One-time import of the real content of the design prototype: the six
 * projects (with screenshots and devlog) and the toolbox. Safe to run again:
 * rows that already exist are left alone.
 *
 *   php artisan db:seed --class=RealContentSeeder
 */
class RealContentSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const TOOLBOX = [
        'daily' => ['PHP', 'Laravel', 'Livewire', 'Alpine.js', 'Tailwind CSS', 'MySQL', 'Git', 'JavaScript', 'HTML', 'CSS', 'Claude'],
        'sometimes' => ['Python', 'Java', 'SQL', 'Linux'],
        'languages' => ['Türkçe', 'Almanca', 'İngilizce'],
    ];

    public function run(ImageStore $images): void
    {
        $this->seedToolbox();
        $this->seedProjects($images);
    }

    private function seedToolbox(): void
    {
        foreach (self::TOOLBOX as $group => $names) {
            foreach ($names as $order => $name) {
                $technology = Technology::query()->firstOrNew(['name' => $name]);

                if ($technology->toolbox_group === null) {
                    $technology->toolbox_group = ToolboxGroup::from($group);
                    $technology->toolbox_order = $order;
                    $technology->save();
                }
            }
        }
    }

    private function seedProjects(ImageStore $images): void
    {
        /** @var list<array{slug: string, name: string, is_featured: bool, status: string, started_year: int, tagline: string, stack: list<string>, cover: ?string, gallery: list<array{url: string, caption: string}>, demo_url: ?string, repo_url: ?string, body: ?string, devlog: list<array{date: string, body: string}>, draft: bool}> $projects */
        $projects = require __DIR__.'/data/projects.php';

        foreach ($projects as $order => $data) {
            if (Project::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $project = Project::query()->create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'status' => $data['status'],
                'started_year' => $data['started_year'],
                'demo_url' => $data['demo_url'],
                'repo_url' => $data['repo_url'],
                'body' => $data['body'],
                'is_featured' => $data['is_featured'],
                'published_at' => $data['draft'] ? null : now(),
                'sort_order' => $order,
            ]);

            $project->syncTechnologyNames($data['stack']);

            if ($data['cover'] !== null && is_file(public_path($data['cover']))) {
                $project->cover_path = $images->store(public_path($data['cover']), 'projects');
                $project->save();
            }

            foreach ($data['gallery'] as $position => $shot) {
                if (is_file(public_path($shot['url']))) {
                    $project->images()->create([
                        'path' => $images->store(public_path($shot['url']), 'projects'),
                        'caption' => $shot['caption'],
                        'sort_order' => $position,
                    ]);
                }
            }

            foreach ($data['devlog'] as $entry) {
                $project->devlog()->create($entry);
            }
        }
    }
}
