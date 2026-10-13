<?php

namespace App\Actions\Notes;

use App\Models\Note;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a note and the one tag that holds it.
 */
class SaveNote
{
    /**
     * @param  array{body: string, published_at: mixed, source_type?: ?string, source_id?: ?int}  $attributes  Validated note columns.
     */
    public function handle(?Note $note, array $attributes, string $tagName): Note
    {
        $note ??= new Note;

        DB::transaction(function () use ($note, $attributes, $tagName): void {
            $note->fill($attributes);
            $note->useTagNamed($tagName);
            $note->save();
        });

        return $note;
    }
}
