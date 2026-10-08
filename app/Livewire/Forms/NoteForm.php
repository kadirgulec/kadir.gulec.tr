<?php

namespace App\Livewire\Forms;

use App\Actions\Notes\SaveNote;
use App\Models\Note;
use App\Support\FormControl;
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

    /** "Y-m-d\TH:i" from a datetime-local input; empty means draft. */
    public string $published_at = '';

    public function setNote(Note $note): void
    {
        $this->note = $note;
        $this->body = $note->body;
        $this->tagName = $note->tag->name;
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
            ],
            $this->tagName,
        );

        $this->setNote($note->fresh(['tag']) ?? $note);

        return $note;
    }
}
