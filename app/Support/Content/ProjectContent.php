<?php

namespace App\Support\Content;

use App\Enums\Permission;
use App\Models\DevlogEntry;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Technology;
use App\Support\Images\ImageStore;
use App\Support\Og\OgUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Projects in the shape the site views expect (the shape the prototype data
 * had), so the views and components did not have to change.
 *
 * @phpstan-type ProjectLog array{date: \Carbon\CarbonImmutable, html: HtmlString, text: string}
 * @phpstan-type ProjectData array{
 *     id: int, slug: string, name: string, isFeatured: bool, isDraft: bool, status: string, since: int, tagline: string,
 *     stack: list<string>, imageUrl: ?string, imageSrcset: ?string, gallery: list<array{url: string, srcset: string, caption: string}>,
 *     demoUrl: ?string, repoUrl: ?string, goalId: ?int, bodyHtml: ?HtmlString, devlog: list<ProjectLog>,
 *     url: string, latestLog: ?ProjectLog, metaDescription: string, ogImage: string
 * }
 */
class ProjectContent
{
    /**
     * Published projects in Kadir's order.
     *
     * @return list<ProjectData>
     */
    public function all(): array
    {
        return array_values($this->query()->published()->get()->map($this->toArray(...))->all());
    }

    /**
     * @return ProjectData|null
     */
    public function featured(): ?array
    {
        $project = $this->query()->published()->where('is_featured', true)->first()
            ?? $this->query()->published()->first();

        return $project ? $this->toArray($project) : null;
    }

    /**
     * A published project, or a draft when the viewer may manage projects.
     *
     * @return ProjectData|null
     */
    public function find(string $slug): ?array
    {
        $project = $this->query()->where('slug', $slug)->first();

        if ($project === null || (! $project->isPublished() && ! Gate::allows(Permission::ManageProjects->value))) {
            return null;
        }

        return $this->toArray($project);
    }

    /**
     * @return Builder<Project>
     */
    private function query(): Builder
    {
        return Project::query()
            ->with(['technologies', 'images', 'devlog'])
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return ProjectData
     */
    public function toArray(Project $project): array
    {
        $devlog = array_values($project->devlog->map(fn (DevlogEntry $entry): array => [
            'date' => $entry->date,
            'html' => new HtmlString((string) $entry->body_html),
            'text' => trim(html_entity_decode(strip_tags((string) $entry->body_html), ENT_QUOTES | ENT_HTML5)),
        ])->all());

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'isFeatured' => $project->is_featured,
            'isDraft' => ! $project->isPublished(),
            'status' => $project->status->value,
            'since' => $project->started_year,
            'tagline' => $project->tagline,
            'stack' => array_values($project->technologies->map(fn (Technology $technology): string => $technology->name)->all()),
            'imageUrl' => $project->coverUrl(),
            'imageSrcset' => ImageStore::srcset($project->cover_path),
            'gallery' => array_values($project->images->map(fn (ProjectImage $image): array => [
                'url' => (string) $image->url(),
                'srcset' => (string) ImageStore::srcset($image->path),
                'caption' => (string) $image->caption,
            ])->all()),
            'demoUrl' => $project->demo_url,
            'repoUrl' => $project->repo_url,
            'goalId' => $project->goal_id,
            'bodyHtml' => filled($project->body_html) ? new HtmlString($project->body_html) : null,
            'devlog' => $devlog,
            'url' => route('projects.show', $project->slug),
            'latestLog' => $devlog[0] ?? null,
            'metaDescription' => $project->meta_description ?: $project->tagline,
            'ogImage' => OgUrl::for('project', $project->slug, $project->updated_at),
        ];
    }
}
