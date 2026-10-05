<?php

namespace App\Livewire\Forms;

use App\Enums\ChainPeriod;
use App\Enums\GoalKind;
use App\Enums\GoalMeasure;
use App\Enums\GoalVisibility;
use App\Models\Goal;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * The fields of a goal; which ones apply depends on its kind.
 */
class GoalForm extends Form
{
    #[Locked]
    public ?Goal $goal = null;

    #[Locked]
    public string $kind = 'chain';

    public string $title = '';

    public string $slug = '';

    public string $visibility = 'hidden';

    public string $parent_id = '';

    public ?int $year = null;

    public string $measure = 'numeric';

    public ?int $target = null;

    public string $unit = '';

    public bool $show_progress_notes = false;

    public string $why = '';

    public ?int $started_year = null;

    public string $started_on = '';

    public string $ended_on = '';

    public string $chain_period = 'day';

    public int $chain_target = 1;

    public function forKind(GoalKind $kind): void
    {
        $this->kind = $kind->value;
        $this->year = now()->year;
        $this->started_on = $kind === GoalKind::Chain ? now()->toDateString() : '';
        $this->started_year = $kind === GoalKind::LongTerm ? now()->year : null;
    }

    public function setGoal(Goal $goal): void
    {
        $this->goal = $goal;
        $this->kind = $goal->kind->value;
        $this->title = $goal->title;
        $this->slug = $goal->slug;
        $this->visibility = $goal->visibility->value;
        $this->parent_id = (string) ($goal->parent_id ?? '');
        $this->year = $goal->year;
        $this->measure = $goal->measure->value ?? GoalMeasure::Numeric->value;
        $this->target = $goal->target;
        $this->unit = (string) $goal->unit;
        $this->show_progress_notes = $goal->show_progress_notes;
        $this->why = (string) $goal->why;
        $this->started_year = $goal->started_year;
        $this->started_on = $goal->started_on?->toDateString() ?? '';
        $this->ended_on = $goal->ended_on?->toDateString() ?? '';
        $this->chain_period = $goal->chain_period->value;
        $this->chain_target = $goal->chain_target;
    }

    public function kind(): GoalKind
    {
        return GoalKind::from($this->kind);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $kind = $this->kind();

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'visibility' => ['required', Rule::enum(GoalVisibility::class)],
            // Only long-term goals can be a parent, and they have none themselves.
            'parent_id' => $kind === GoalKind::LongTerm
                ? ['prohibited']
                : ['nullable', Rule::exists('goals', 'id')->where('kind', GoalKind::LongTerm->value)],
            'year' => $kind === GoalKind::Yearly ? ['required', 'integer', 'between:2000,2100'] : ['nullable'],
            'measure' => $kind === GoalKind::Yearly ? ['required', Rule::enum(GoalMeasure::class)] : ['nullable'],
            'target' => $kind === GoalKind::Yearly && $this->measure === GoalMeasure::Numeric->value ? ['required', 'integer', 'min:1'] : ['nullable', 'integer'],
            'unit' => ['nullable', 'string', 'max:40'],
            'show_progress_notes' => ['boolean'],
            'why' => ['nullable', 'string', 'max:5000'],
            'started_year' => ['nullable', 'integer', 'between:1980,2100'],
            'started_on' => $kind === GoalKind::Chain ? ['required', 'date'] : ['nullable'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'chain_period' => $kind === GoalKind::Chain ? ['required', Rule::enum(ChainPeriod::class)] : ['nullable'],
            // A daily chain has no target to count up to; store() keeps it at one.
            'chain_target' => $kind === GoalKind::Chain && $this->chain_period !== ChainPeriod::Day->value
                ? ['required', 'integer', 'min:1', 'max:'.(ChainPeriod::tryFrom($this->chain_period) ?? ChainPeriod::Day)->maxTarget()]
                : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'parent_id' => 'üst hedef',
            'measure' => 'ölçü',
            'started_on' => 'başlangıç',
            'ended_on' => 'bitiş',
            'started_year' => 'başlangıç yılı',
            'chain_period' => 'birim',
            'chain_target' => 'hedef',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'parent_id.prohibited' => 'Uzun vadeli bir hedefin üst hedefi olmaz.',
            'parent_id.exists' => 'Üst hedef uzun vadeli bir hedef olmalı.',
        ];
    }

    public function store(): Goal
    {
        $this->validate();

        $kind = $this->kind();
        $goal = $this->goal ?? new Goal(['kind' => $kind]);
        $isNew = ! $goal->exists;

        $goal->fill([
            'title' => $this->title,
            'slug' => $this->slug !== '' ? $this->slug : null,
            'visibility' => $this->visibility,
            'parent_id' => $kind !== GoalKind::LongTerm && $this->parent_id !== '' ? (int) $this->parent_id : null,
            'year' => $kind === GoalKind::Yearly ? $this->year : null,
            'measure' => $kind === GoalKind::Yearly ? $this->measure : null,
            'target' => $kind === GoalKind::Yearly && $this->measure === GoalMeasure::Numeric->value ? $this->target : null,
            'unit' => $kind === GoalKind::Yearly && $this->unit !== '' ? $this->unit : null,
            'show_progress_notes' => $kind === GoalKind::Yearly && $this->show_progress_notes,
            'why' => $kind === GoalKind::LongTerm && $this->why !== '' ? $this->why : null,
            'started_year' => $kind === GoalKind::LongTerm ? $this->started_year : null,
            'started_on' => $kind === GoalKind::Chain && $this->started_on !== '' ? CarbonImmutable::parse($this->started_on) : null,
            'ended_on' => $kind === GoalKind::Chain && $this->ended_on !== '' ? CarbonImmutable::parse($this->ended_on) : null,
            'chain_period' => $kind === GoalKind::Chain ? $this->chain_period : ChainPeriod::Day->value,
            'chain_target' => $kind === GoalKind::Chain && $this->chain_period !== ChainPeriod::Day->value ? $this->chain_target : 1,
        ]);

        if ($isNew) {
            $goal->sort_order = $goal->nextSortOrder();
        }

        $goal->save();
        $this->setGoal($goal->fresh() ?? $goal);

        return $goal;
    }
}
