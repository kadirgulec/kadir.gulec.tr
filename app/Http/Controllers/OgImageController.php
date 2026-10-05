<?php

namespace App\Http\Controllers;

use App\Enums\GoalKind;
use App\Enums\GoalVisibility;
use App\Enums\Section;
use App\Enums\WatchableType;
use App\Models\Goal;
use App\Models\Post;
use App\Models\Project;
use App\Models\Watchable;
use App\Support\ChainStats;
use App\Support\Images\ImageStore;
use App\Support\Og\OgImage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves link preview images. Each one is drawn once, on its first request,
 * and kept on the public disk until its content changes. Always drawn as a
 * visitor would see the page: drafts and hidden goals have none, censored
 * goals stay censored.
 *
 * @phpstan-import-type Card from OgImage
 */
class OgImageController extends Controller
{
    public function __invoke(string $kind, string $key, OgImage $og): Response
    {
        [$card, $version] = $this->card($kind, $key) ?? abort(404);

        $file = 'og/'.$kind.'-'.md5($key).'-'.($version?->getTimestamp() ?? 0).'.png';
        $disk = Storage::disk('public');

        if (! $disk->exists($file)) {
            foreach ($disk->files('og') as $old) {
                if (str_starts_with($old, 'og/'.$kind.'-'.md5($key).'-')) {
                    $disk->delete($old);
                }
            }

            $disk->put($file, $og->render($card));
        }

        return response()->file($disk->path($file), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function card(string $kind, string $key): ?array
    {
        return match ($kind) {
            'page' => $this->page($key),
            'post' => $this->post($key),
            'project' => $this->project($key),
            'film', 'dizi' => $this->watchable(WatchableType::fromRouteSegment($kind), $key),
            'goal' => $this->goal($key),
            default => null,
        };
    }

    /**
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function page(string $key): ?array
    {
        $section = Section::tryFrom($key);

        if ($section === null) {
            return null;
        }

        return [[
            'title' => $section === Section::Home ? 'Kadir Gülec' : $section->label(),
            'kicker' => 'dijital defter',
            'subtitle' => 'Ankara\'da memurdum, Düren\'de yazılımcıyım. Arada bir sürü şey oldu, burası o defter.',
            'section' => $section,
        ], null];
    }

    /**
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function post(string $slug): ?array
    {
        $post = Post::query()->published()->where('slug', $slug)->first();

        return $post ? [['title' => $post->title, 'kicker' => 'Yazılar', 'subtitle' => $post->excerptText(), 'section' => Section::Posts], $post->updated_at] : null;
    }

    /**
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function project(string $slug): ?array
    {
        $project = Project::query()->published()->where('slug', $slug)->first();

        return $project ? [['title' => $project->name, 'kicker' => 'Projeler · '.mb_strtolower($project->status->label()), 'subtitle' => $project->tagline, 'section' => Section::Projects], $project->updated_at] : null;
    }

    /**
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function watchable(?WatchableType $type, string $slug): ?array
    {
        $watchable = $type ? Watchable::query()->published()->where('type', $type)->where('slug', $slug)->first() : null;

        if ($watchable === null) {
            return null;
        }

        return [[
            'title' => $watchable->title,
            'kicker' => 'İzlediklerim · '.mb_strtolower($watchable->type->label()),
            'subtitle' => collect([$watchable->creator, $watchable->year])->filter()->implode(' · '),
            'section' => Section::Watched,
            'poster' => $watchable->poster_path ? Storage::disk('public')->path(ImageStore::file($watchable->poster_path, 480)) : null,
            'grade' => $watchable->rating,
        ], $watchable->updated_at];
    }

    /**
     * Chains and long-term goals; the key is the slug or, for a censored goal, "k-{id}".
     *
     * @return array{0: Card, 1: ?CarbonInterface}|null
     */
    private function goal(string $key): ?array
    {
        $goal = preg_match('/^k-(\d+)$/', $key, $match)
            ? Goal::query()->whereKey((int) $match[1])->where('visibility', GoalVisibility::Censored)->first()
            : Goal::query()->where('slug', $key)->where('visibility', GoalVisibility::Public)->first();

        if ($goal === null || $goal->kind === GoalKind::Yearly || ($goal->kind === GoalKind::LongTerm && $goal->visibility !== GoalVisibility::Public)) {
            return null;
        }

        $censored = $goal->visibility === GoalVisibility::Censored;
        $subtitle = $goal->kind === GoalKind::Chain
            ? ChainStats::currentStreak(array_column($goal->load('chainDays')->chainLinks(), 'state')).' '.$goal->chain_period->adjective().' seri'
            : strip_tags((string) $goal->why_html);

        return [[
            'title' => $censored ? null : $goal->title,
            'titleLength' => mb_strlen($goal->title),
            'kicker' => 'Hedefler · '.mb_strtolower($goal->kind === GoalKind::Chain ? 'zincir' : 'uzun vade'),
            'subtitle' => $subtitle,
            'section' => Section::Goals,
        ], $goal->kind === GoalKind::Chain ? now()->startOfDay() : $goal->updated_at];
    }
}
