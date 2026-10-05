<?php

namespace App\Support;

use App\Enums\GoalKind;
use App\Enums\GoalVisibility;
use App\Models\Goal;
use App\Models\Post;
use App\Models\Project;
use App\Models\Watchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Bunu mu aradın?" on the 404 page: the published page whose address is
 * closest to the one that was asked for. Renamed slugs never get here, they
 * are redirected first (bootstrap/app.php). Only public pages are offered:
 * drafts and censored or hidden goals would give away what they hide.
 */
class NotFoundSuggestion
{
    /**
     * Below this similarity (in percent) a guess is more confusing than helpful.
     */
    private const THRESHOLD = 60;

    /**
     * @return array{title: string, url: string}|null
     */
    public static function for(string $path): ?array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $needle = Str::slug((string) end($segments));

        if (mb_strlen($needle) < 3) {
            return null;
        }

        try {
            $candidates = self::candidates();
        } catch (Throwable) {
            // The error page must render even when the database does not.
            return null;
        }

        $best = $candidates
            ->map(fn (array $candidate): array => [...$candidate, 'score' => self::score($needle, $candidate['slug'], $segments[0] ?? '', $candidate['url'])])
            ->sortByDesc('score')
            ->first();

        return $best !== null && $best['score'] >= self::THRESHOLD
            ? ['title' => $best['title'], 'url' => $best['url']]
            : null;
    }

    /**
     * @return Collection<int, array{title: string, slug: string, url: string}>
     */
    private static function candidates(): Collection
    {
        $entry = fn (string $title, string $slug, string $path): array => ['title' => $title, 'slug' => $slug, 'url' => url($path)];

        return collect()
            ->merge(Post::query()->published()->get()->map(fn (Post $post) => $entry($post->title, $post->slug, $post->publicPath())))
            ->merge(Project::query()->published()->get()->map(fn (Project $project) => $entry($project->name, $project->slug, $project->publicPath())))
            ->merge(Watchable::query()->published()->get()->map(fn (Watchable $watchable) => $entry($watchable->title, $watchable->slug, $watchable->publicPath())))
            ->merge(Goal::query()
                ->where('visibility', GoalVisibility::Public)
                ->whereIn('kind', [GoalKind::Chain, GoalKind::LongTerm])
                ->get()
                ->map(fn (Goal $goal) => $entry($goal->title, $goal->slug, $goal->publicPath())));
    }

    /**
     * Similarity of the slugs in percent. A slug that contains the other one
     * counts as a near match ("neden-blog" → "neden-blog-yazisi"), and a page
     * in the section the address started with wins a tie.
     */
    private static function score(string $needle, string $slug, string $section, string $url): float
    {
        similar_text($needle, $slug, $percent);

        if (mb_strlen($needle) >= 4 && (str_contains($slug, $needle) || str_contains($needle, $slug))) {
            $percent = max($percent, 85);
        }

        if ($section !== '' && str_contains($url, '/'.$section.'/')) {
            $percent += 5;
        }

        return $percent;
    }
}
