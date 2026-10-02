<?php

namespace Database\Seeders;

use App\Actions\Watched\StorePoster;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Models\Goal;
use App\Models\Post;
use App\Models\Project;
use App\Models\Watchable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * The sample content of the design prototype (posts, and in later steps
 * films and goals), so the site looks filled on a local machine.
 * DatabaseSeeder only calls it outside production.
 */
class DemoSeeder extends Seeder
{
    public function run(StorePoster $storePoster): void
    {
        $this->seedPosts();
        $this->seedWatched($storePoster);
        $this->seedGoals();
    }

    private function seedPosts(): void
    {
        /** @var list<array{title: string, slug: string, excerpt: string, published_at: string, tags: list<string>, is_featured: bool, body: string}> $posts */
        $posts = require __DIR__.'/data/demo-posts.php';

        foreach ($posts as $data) {
            if (Post::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $post = Post::query()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'],
                'body' => $data['body'],
                'is_featured' => $data['is_featured'],
                'published_at' => $data['published_at'],
            ]);

            $post->syncTagNames($data['tags']);
        }
    }

    private function seedWatched(StorePoster $storePoster): void
    {
        /** @var list<array<string, mixed>> $entries */
        $entries = require __DIR__.'/data/demo-watched.php';

        foreach ($entries as $data) {
            if (Watchable::query()->where('type', $data['type'])->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $watchedOn = CarbonImmutable::parse($data['watched_on']);

            $watchable = Watchable::query()->create([
                'type' => $data['type'],
                'slug' => $data['slug'],
                'title' => $data['title'],
                'original_title' => $data['original_title'],
                'year' => $data['year'],
                'creator' => $data['creator'],
                'genres' => $data['genres'],
                'runtime_minutes' => $data['runtime_minutes'],
                'overview' => $data['overview'],
                'cast' => $data['cast'],
                'rating' => $data['rating'],
                'is_favorite' => $data['is_favorite'],
                'review' => $data['review'],
                'review_published_at' => $data['review'] !== null ? $watchedOn->addDay() : null,
                'series_status' => $data['series_status'],
                'current_season' => $data['current_season'],
                'current_episode' => $data['current_episode'],
                'published_at' => $watchedOn,
            ]);

            foreach ($data['seasons'] as $season) {
                $watchable->seasons()->create($season);
            }

            if ($data['is_rewatch']) {
                $watchable->viewings()->create(['watched_on' => $watchedOn->subYears(3), 'place' => 'Evde']);
            }

            $watchable->viewings()->create(['watched_on' => $watchedOn, 'place' => $data['place']]);

            $poster = public_path('images/prototype/posters/'.$data['poster_file']);

            if (is_file($poster)) {
                $storePoster->handle($watchable, $poster);
            } else {
                $watchable->forceFill(['accent' => $data['accent'], 'poster_colors' => $data['poster_colors']])->save();
            }
        }
    }

    private function seedGoals(): void
    {
        if (Goal::query()->exists()) {
            return;
        }

        /** @var array{chains: list<array<string, mixed>>, yearly: list<array<string, mixed>>, long_term: list<array<string, mixed>>, past: list<array<string, mixed>>} $data */
        $data = require __DIR__.'/data/demo-goals.php';
        $today = CarbonImmutable::today();
        $ids = [];

        foreach ($data['long_term'] as $order => $goal) {
            $longTerm = Goal::query()->create([
                'kind' => GoalKind::LongTerm, 'slug' => $goal['slug'], 'title' => $goal['title'], 'why' => $goal['why'],
                'visibility' => $goal['visibility'], 'started_year' => $goal['started_year'], 'sort_order' => $order,
            ]);

            foreach ($goal['updates'] as $update) {
                $longTerm->updates()->create($update);
            }

            $ids[$goal['slug']] = $longTerm->id;
        }

        foreach ($data['yearly'] as $order => $goal) {
            $yearly = Goal::query()->create([
                'kind' => GoalKind::Yearly, 'slug' => $goal['slug'], 'title' => $goal['title'], 'visibility' => $goal['visibility'],
                'year' => $today->year, 'measure' => $goal['measure'], 'target' => $goal['target'], 'unit' => $goal['unit'],
                'achieved_at' => $goal['achieved_at'], 'parent_id' => $ids[$goal['parent']] ?? null, 'sort_order' => $order,
                'show_progress_notes' => $goal['unit'] === 'kitap',
            ]);

            if ($goal['measure'] === GoalMeasure::Numeric->value && $goal['current']) {
                $steps = $goal['current'] <= 30 ? $goal['current'] : 1;

                for ($step = 0; $step < $steps; $step++) {
                    $yearly->progressEntries()->create([
                        'date' => $today->startOfYear()->addDays((int) floor($today->dayOfYear / max(1, $steps) * $step)),
                        'amount' => $steps === 1 ? $goal['current'] : 1,
                        'note' => $goal['unit'] === 'kitap' ? 'Kitap '.($step + 1) : null,
                    ]);
                }
            }

            foreach ($goal['milestones'] as $position => $milestone) {
                $yearly->milestones()->create(['title' => $milestone['title'], 'done_at' => $milestone['done'] ? $today->subWeeks(10 - $position) : null, 'sort_order' => $position]);
            }

            if ($goal['project'] !== null) {
                Project::query()->where('slug', $goal['project'])->update(['goal_id' => $yearly->id]);
            }
        }

        foreach ($data['past'] as $order => $goal) {
            Goal::query()->create([
                'kind' => GoalKind::Yearly, 'title' => $goal['title'], 'visibility' => 'public', 'year' => $goal['year'],
                'measure' => GoalMeasure::Binary, 'achieved_at' => $goal['achieved'] ? CarbonImmutable::create($goal['year'], 11, 1) : null, 'sort_order' => $order,
            ]);
        }

        foreach ($data['chains'] as $order => $chain) {
            $days = str_split($chain['pattern']);
            $start = $today->subDays(count($days) - 1);

            $goal = Goal::query()->create([
                'kind' => GoalKind::Chain, 'slug' => $chain['slug'], 'title' => $chain['title'], 'visibility' => $chain['visibility'],
                'parent_id' => $ids[$chain['parent']] ?? null, 'started_on' => $start, 'sort_order' => $order,
            ]);

            $rows = [];

            foreach ($days as $offset => $day) {
                if ($day !== '-') {
                    $rows[] = ['date' => $start->addDays($offset)->toDateString(), 'state' => $day === 'x' ? 'done' : 'excused'];
                }
            }

            $goal->chainDays()->createMany($rows);
        }
    }
}
