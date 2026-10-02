<?php

namespace App\Livewire\Forms;

use App\Actions\Projects\SaveProject;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Technology;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * The fields of the project editor and how they map onto a Project.
 */
class ProjectForm extends Form
{
    #[Locked]
    public ?Project $project = null;

    public string $name = '';

    public string $slug = '';

    public string $tagline = '';

    public string $status = 'in-progress';

    public ?int $started_year = null;

    public string $demo_url = '';

    public string $repo_url = '';

    public string $body = '';

    public string $meta_description = '';

    public bool $is_featured = false;

    /** "Y-m-d\TH:i" from a datetime-local input; empty means draft. */
    public string $published_at = '';

    /** @var list<string> */
    public array $technologyNames = [];

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public bool $removeCover = false;

    public function setProject(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
        $this->slug = $project->slug;
        $this->tagline = $project->tagline;
        $this->status = $project->status->value;
        $this->started_year = $project->started_year;
        $this->demo_url = (string) $project->demo_url;
        $this->repo_url = (string) $project->repo_url;
        $this->body = (string) $project->body;
        $this->meta_description = (string) $project->meta_description;
        $this->is_featured = $project->is_featured;
        $this->published_at = $project->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->technologyNames = array_values($project->technologies->map(fn (Technology $technology): string => $technology->name)->all());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'tagline' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'started_year' => ['required', 'integer', 'between:1990,'.(now()->year + 1)],
            'demo_url' => ['nullable', 'url:https,http', 'max:255'],
            'repo_url' => ['nullable', 'url:https,http', 'max:255'],
            'body' => ['nullable', 'string', 'max:100000'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'technologyNames' => ['array', 'max:20'],
            'technologyNames.*' => ['string', 'max:40'],
            'cover' => ['nullable', 'image', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'technologyNames' => 'teknolojiler',
            'technologyNames.*' => 'teknoloji',
            'cover' => 'kapak görseli',
        ];
    }

    public function store(SaveProject $saveProject): Project
    {
        $this->validate();

        $project = $saveProject->handle(
            $this->project,
            [
                'name' => $this->name,
                'slug' => $this->slug !== '' ? $this->slug : null,
                'tagline' => $this->tagline,
                'status' => $this->status,
                'started_year' => $this->started_year,
                'demo_url' => $this->demo_url !== '' ? $this->demo_url : null,
                'repo_url' => $this->repo_url !== '' ? $this->repo_url : null,
                'body' => $this->body !== '' ? $this->body : null,
                'meta_description' => $this->meta_description !== '' ? $this->meta_description : null,
                'is_featured' => $this->is_featured,
                'published_at' => $this->published_at !== '' ? CarbonImmutable::parse($this->published_at) : null,
            ],
            $this->technologyNames,
            $this->cover?->getRealPath() ?: null,
            $this->removeCover,
        );

        $this->cover = null;
        $this->removeCover = false;
        $this->setProject($project->fresh(['technologies']) ?? $project);

        return $project;
    }
}
