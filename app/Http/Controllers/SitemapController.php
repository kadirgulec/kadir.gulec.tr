<?php

namespace App\Http\Controllers;

use App\Enums\GoalKind;
use App\Enums\GoalVisibility;
use App\Enums\Section;
use App\Models\Goal;
use App\Models\MonthlyReview;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\Watchable;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * sitemap.xml and robots.txt. The sitemap lists only what any visitor can
 * read and search engines should find: published content and public long-term
 * goals (never censored or hidden ones). Chains and the legal pages are
 * noindex, so they stay out. A section's lastmod is its newest entry's.
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $entries = [
            Section::Posts->value => Post::query()->published()->get(['slug', 'updated_at'])->map(fn (Post $post): array => $this->entry($post->publicPath(), $post->updated_at)),
            Section::Notes->value => Note::query()->published()->get(['id', 'updated_at'])->map(fn (Note $note): array => $this->entry($note->publicPath(), $note->updated_at)),
            Section::Shelf->value => Watchable::query()->published()->get(['type', 'slug', 'updated_at'])->map(fn (Watchable $watchable): array => $this->entry($watchable->publicPath(), $watchable->updated_at)),
            Section::Goals->value => Goal::query()->where('visibility', GoalVisibility::Public)->where('kind', GoalKind::LongTerm)->get(['kind', 'slug', 'updated_at'])->map(fn (Goal $goal): array => $this->entry($goal->publicPath(), $goal->updated_at)),
            Section::Projects->value => Project::query()->published()->get(['slug', 'updated_at'])->map(fn (Project $project): array => $this->entry($project->publicPath(), $project->updated_at)),
        ];

        $reviews = MonthlyReview::query()->published()->get(['month', 'updated_at'])->map(fn (MonthlyReview $review): array => $this->entry($review->publicPath(), $review->updated_at));

        if ($reviews->isNotEmpty()) {
            $entries[Section::Goals->value] = $entries[Section::Goals->value]->toBase()
                ->push($this->entry(route('goals.reviews.index', absolute: false), $reviews->max('lastmod')))
                ->merge($reviews);
        }

        $everything = collect($entries)->flatten(1);

        $sections = collect(Section::cases())->map(fn (Section $section): array => [
            'loc' => $section->url(),
            'lastmod' => match ($section) {
                Section::Home => $everything->max('lastmod'),
                Section::About => null,
                default => $entries[$section->value]->max('lastmod'),
            },
        ]);

        return response()->view('feeds.sitemap', ['urls' => $sections->merge($everything)])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return array{loc: string, lastmod: ?CarbonInterface}
     */
    private function entry(string $path, ?CarbonInterface $lastmod): array
    {
        return ['loc' => url($path), 'lastmod' => $lastmod];
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /hesap',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /two-factor-challenge',
            'Disallow: /user/',
            'Disallow: /bildirimler/',
            'Disallow: /takip/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
