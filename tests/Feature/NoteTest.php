<?php

use App\Models\Note;
use App\Models\Tag;

it('holds a note by an existing tag of that name, or a new one', function () {
    $laravel = Tag::factory()->create(['name' => 'laravel']);

    $existing = Note::factory()->make();
    $existing->useTagNamed(' laravel ');
    $existing->save();

    $new = Note::factory()->make();
    $new->useTagNamed('yüzme');
    $new->save();

    expect($existing->tag_id)->toBe($laravel->id)
        ->and($new->tag->slug)->toBe('yuzme');
});

it('renders the Markdown body when saved', function () {
    $note = Note::factory()->create(['body' => 'Bir ==vurgu==.']);

    expect($note->body_html)->toBe('<p>Bir <mark class="marker">vurgu</mark>.</p>');
});
