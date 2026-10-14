<?php

namespace App\Livewire\Forms;

use App\Actions\Notes\SaveNote;
use App\Models\Book;
use App\Models\Note;
use App\Models\Watchable;
use App\Support\FormControl;
use Closure;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * The fields of the note editor (and of the dashboard's quick note).
 */
class NoteForm extends Form
{
    /** Past this length the counter warns: the note grows long. */
    public const SOFT_LIMIT = 250;

    /** Past this length the counter turns red: this may want to be a post. */
    public const HARD_HINT = 500;

    #[Locked]
    public ?Note $note = null;

    public string $body = '';

    public string $tagName = '';

    /** Where the note came from: "book:12", "watchable:3", or empty for none. */
    public string $source = '';

    /** "Y-m-d\TH:i" from a datetime-local input; empty means draft. */
    public string $published_at = '';

    public function setNote(Note $note): void
    {
        $this->note = $note;
        $this->body = $note->body;
        $this->tagName = $note->tag->name;
        $this->source = $note->source_type !== null ? $note->source_type.':'.$note->source_id : '';
        $this->published_at = FormControl::dateTimeLocal($note->published_at);
    }

    /**
     * A new note is published the moment it is saved, unless the date is emptied.
     */
    public function startNew(): void
    {
        $this->published_at = FormControl::dateTimeLocal(now());
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'tagName' => ['required', 'string', 'max:40'],
            'source' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (filled($value) && self::resolveSource((string) $value) === null) {
                    $fail('Bu kaynak bulunamadı.');
                }
            }],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'body' => 'not',
            'tagName' => 'etiket',
            'source' => 'kaynak',
            'published_at' => 'yayın tarihi',
        ];
    }

    public function store(SaveNote $saveNote): Note
    {
        $this->validate();

        $note = $saveNote->handle(
            $this->note,
            [
                'body' => trim($this->body),
                'published_at' => FormControl::parseDateTimeLocal($this->published_at),
                ...$this->sourceColumns(),
            ],
            $this->tagName,
        );

        $this->setNote($note->fresh(['tag']) ?? $note);

        return $note;
    }

    /**
     * @return array{source_type: ?string, source_id: ?int}
     */
    private function sourceColumns(): array
    {
        $source = self::resolveSource($this->source);

        return ['source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey()];
    }

    /**
     * The book or film/series a "type:id" value points at.
     */
    public static function resolveSource(string $value): Book|Watchable|null
    {
        if (preg_match('/^(book|watchable):(\d+)$/', $value, $match) !== 1) {
            return null;
        }

        return $match[1] === 'book' ? Book::query()->find((int) $match[2]) : Watchable::query()->find((int) $match[2]);
    }
}
