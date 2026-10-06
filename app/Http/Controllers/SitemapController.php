<?php

namespace App\Http\Controllers;

use App\Enums\GoalKind;
use App\Enums\GoalVisibility;
use App\Enums\Section;
use App\Models\Goal;
use App\Models\Note;
use App\Models\Post;
use App\Models\Project;
use App\Models\Watchable;
use Illuminate\Http\Response;

/**
 * sitemap.xml and robots.txt. The sitemap lists only what any visitor can
 * read: published content and public goals (never censored or hidden ones).
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(Section::cases())->map(fn (Section $section): array => ['loc' => $section->url(), 'lastmod' => null]);

        $urls = $urls->merge(Post::query()->published()->get(['slug', 'updated_at'])->map(fn (Post $post): array => ['loc' => route('posts.show', $post->slug), 'lastmod' => $post->updated_at]))
            ->merge(Note::query()->published()->get(['id', 'updated_at'])->map(fn (Note $note): array => ['loc' => route('notes.show', $note->id), 'lastmod' => $note->updated_at]))
            ->merge(Project::query()->published()->get(['slug', 'updated_at'])->map(fn (Project $project): array => ['loc' => route('projects.show', $project->slug), 'lastmod' => $project->updated_at]))
            ->merge(Watchable::query()->published()->get(['type', 'slug', 'updated_at'])->map(fn (Watchable $watchable): array => ['loc' => route('watched.show', ['type' => $watchable->type->routeSegment(), 'slug' => $watchable->slug]), 'lastmod' => $watchable->updated_at]))
            ->merge(Goal::query()->where('visibility', GoalVisibility::Public)->whereIn('kind', [GoalKind::Chain, GoalKind::LongTerm])->get(['kind', 'slug', 'updated_at'])->map(fn (Goal $goal): array => [
                'loc' => $goal->kind === GoalKind::Chain ? route('goals.chain', $goal->slug) : route('goals.show', $goal->slug),
                'lastmod' => $goal->updated_at,
            ]))
            ->push(['loc' => route('privacy'), 'lastmod' => null])
            ->push(['loc' => route('imprint'), 'lastmod' => null]);

        return response()->view('feeds.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
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
