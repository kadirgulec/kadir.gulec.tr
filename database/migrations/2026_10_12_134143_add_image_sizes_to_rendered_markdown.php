<?php

use App\Models\DevlogEntry;
use App\Models\Goal;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\ReviewItem;
use App\Models\Watchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Images in Markdown now carry width and height (PolaroidRenderer). The
     * HTML is stored when a row is saved, so render it once more for what
     * already exists. Only rows whose HTML changes are written, and their
     * updated_at stays: nothing was edited, so the sitemap and the preview
     * images keep their dates.
     */
    public function up(): void
    {
        $models = [Post::class, Note::class, Project::class, DevlogEntry::class, Watchable::class, Goal::class, ReviewItem::class];

        foreach ($models as $class) {
            $class::query()->lazyById()->each(function (Model $model): void {
                $model->renderMarkdownColumns(force: true);

                if ($model->isDirty()) {
                    $model->timestamps = false;
                    $model->saveQuietly();
                }
            });
        }
    }

    /**
     * Nothing to undo: the extra attributes are harmless.
     */
    public function down(): void
    {
        //
    }
};
