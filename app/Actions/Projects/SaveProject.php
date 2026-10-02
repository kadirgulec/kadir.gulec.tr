<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Support\Images\ImageStore;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a project with its technologies and cover image.
 * A replaced or removed cover deletes its old files.
 */
class SaveProject
{
    public function __construct(private ImageStore $images) {}

    /**
     * @param  array<string, mixed>  $attributes  Validated project columns.
     * @param  list<string>  $technologyNames
     */
    public function handle(?Project $project, array $attributes, array $technologyNames, ?string $newCoverPath = null, bool $removeCover = false): Project
    {
        $project ??= new Project;
        $oldCover = $project->cover_path;

        if ($newCoverPath !== null) {
            $project->cover_path = $this->images->store($newCoverPath, 'projects');
        } elseif ($removeCover) {
            $project->cover_path = null;
        }

        DB::transaction(function () use ($project, $attributes, $technologyNames): void {
            $project->fill($attributes);

            if (! $project->exists) {
                $project->sort_order = $project->nextSortOrder();
            }

            $project->save();
            $project->syncTechnologyNames($technologyNames);
        });

        if ($oldCover !== null && $oldCover !== $project->cover_path) {
            $this->images->delete($oldCover);
        }

        return $project;
    }
}
