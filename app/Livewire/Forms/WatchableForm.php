<?php

namespace App\Livewire\Forms;

use App\Enums\SeriesStatus;
use App\Models\Watchable;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * The editable fields of a film or series: the facts (prefilled from TMDB)
 * and Kadir's take on it.
 */
class WatchableForm extends Form
{
    #[Locked]
    public ?Watchable $watchable = null;

    public string $title = '';

    public string $slug = '';

    public string $original_title = '';

    public ?int $year = null;

    public string $creator = '';

    /** @var list<string> */
    public array $genres = [];

    public ?int $runtime_minutes = null;

    public string $overview = '';

    /** One "Name — Role" per line. */
    public string $castText = '';

    /** A mark out of ten in half steps, or "" for none. */
    public string $rating = '';

    public bool $is_favorite = false;

    public string $review = '';

    public string $review_published_at = '';

    public string $series_status = '';

    public ?int $current_season = null;

    public ?int $current_episode = null;

    public string $meta_description = '';

    public string $published_at = '';

    public function setWatchable(Watchable $watchable): void
    {
        $this->watchable = $watchable;
        $this->title = $watchable->title;
        $this->slug = $watchable->slug;
        $this->original_title = (string) $watchable->original_title;
        $this->year = $watchable->year;
        $this->creator = (string) $watchable->creator;
        $this->genres = $watchable->genres ?? [];
        $this->runtime_minutes = $watchable->runtime_minutes;
        $this->overview = (string) $watchable->overview;
        $this->castText = implode("\n", array_map(fn (array $member): string => trim($member['name'].' — '.$member['role'], ' —'), $watchable->cast ?? []));
        $this->rating = $watchable->rating !== null ? number_format($watchable->rating, 1, '.', '') : '';
        $this->is_favorite = $watchable->is_favorite;
        $this->review = (string) $watchable->review;
        $this->review_published_at = $watchable->review_published_at?->format('Y-m-d\TH:i') ?? '';
        $this->series_status = $watchable->series_status->value ?? '';
        $this->current_season = $watchable->current_season;
        $this->current_episode = $watchable->current_episode;
        $this->meta_description = (string) $watchable->meta_description;
        $this->published_at = $watchable->published_at?->format('Y-m-d\TH:i') ?? '';
    }

    /**
     * @return list<string> 0.0, 0.5 … 10.0
     */
    public static function ratingSteps(): array
    {
        return array_map(fn (int $half): string => number_format($half / 2, 1, '.', ''), range(0, 20));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $isSeries = $this->watchable?->isSeries() ?? false;

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'original_title' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'between:1888,'.(now()->year + 2)],
            'creator' => ['nullable', 'string', 'max:200'],
            'genres' => ['array', 'max:10'],
            'genres.*' => ['string', 'max:40'],
            'runtime_minutes' => ['nullable', 'integer', 'between:1,1000'],
            'overview' => ['nullable', 'string', 'max:5000'],
            'castText' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', Rule::in(self::ratingSteps())],
            'is_favorite' => ['boolean'],
            'review' => ['nullable', 'string', 'max:100000'],
            'review_published_at' => ['nullable', 'date'],
            'series_status' => [$isSeries ? 'nullable' : 'prohibited', Rule::enum(SeriesStatus::class)],
            'current_season' => ['nullable', 'integer', 'min:1'],
            'current_episode' => ['nullable', 'integer', 'min:0'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'original_title' => 'orijinal ad',
            'creator' => 'yönetmen / yaratıcı',
            'genres' => 'türler',
            'runtime_minutes' => 'süre',
            'overview' => 'özet',
            'castText' => 'oyuncular',
            'review_published_at' => 'yorumun yayın tarihi',
            'series_status' => 'dizi durumu',
            'current_season' => 'sezon',
            'current_episode' => 'bölüm',
        ];
    }

    public function store(): Watchable
    {
        $this->validate();

        $watchable = $this->watchable ?? abort(404);

        $watchable->fill([
            'title' => $this->title,
            'slug' => $this->slug !== '' ? $this->slug : null,
            'original_title' => $this->original_title !== '' ? $this->original_title : null,
            'year' => $this->year,
            'creator' => $this->creator !== '' ? $this->creator : null,
            'genres' => $this->genres,
            'runtime_minutes' => $this->runtime_minutes,
            'overview' => $this->overview !== '' ? $this->overview : null,
            'cast' => $this->parseCast($this->castText),
            'rating' => $this->rating !== '' ? (float) $this->rating : null,
            'is_favorite' => $this->is_favorite,
            'review' => $this->review !== '' ? $this->review : null,
            'review_published_at' => $this->review_published_at !== '' ? CarbonImmutable::parse($this->review_published_at) : null,
            'series_status' => $watchable->isSeries() && $this->series_status !== '' ? $this->series_status : null,
            'current_season' => $watchable->isSeries() ? $this->current_season : null,
            'current_episode' => $watchable->isSeries() ? $this->current_episode : null,
            'meta_description' => $this->meta_description !== '' ? $this->meta_description : null,
            'published_at' => $this->published_at !== '' ? CarbonImmutable::parse($this->published_at) : null,
        ])->save();

        $this->setWatchable($watchable->fresh() ?? $watchable);

        return $watchable;
    }

    /**
     * @return list<array{name: string, role: string}>
     */
    private function parseCast(string $text): array
    {
        $cast = [];

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            [$name, $role] = array_pad(array_map('trim', preg_split('/\s+[—–-]\s+/u', $line, 2) ?: []), 2, '');

            if ($name !== '') {
                $cast[] = ['name' => $name, 'role' => $role];
            }
        }

        return $cast;
    }
}
