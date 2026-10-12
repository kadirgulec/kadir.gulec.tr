<?php

namespace App\Http\Controllers;

use App\Support\Content\NoteContent;
use App\Support\Content\PostContent;
use App\Support\Og\OgUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotesController extends Controller
{
    public function __construct(private NoteContent $notes, private PostContent $posts) {}

    /**
     * The post-it board: newest notes first in masonry columns, a page at a time, with a tag filter (?etiket=slug).
     */
    public function index(Request $request): View
    {
        $tags = collect($this->notes->tags());
        $activeTag = $request->string('etiket')->toString() ?: null;

        abort_if($activeTag !== null && ! $tags->contains('slug', $activeTag), 404);

        $notes = $this->notes->board($activeTag);
        $page = $notes->currentPage();

        // Past the last page there is nothing to show: a real 404, not an empty board.
        abort_if($page > 1 && $page > $notes->lastPage(), 404);

        return view('site.notes.index', [
            'notes' => $notes,
            // A later page holds other notes than the first: it is its own address.
            'canonical' => $page > 1 ? route('notes.index', array_filter(['etiket' => $activeTag, 'sayfa' => $page])) : null,
            'noteCount' => $tags->sum('count'),
            'tags' => $tags->all(),
            'activeTag' => $tags->firstWhere('slug', $activeTag),
        ]);
    }

    /**
     * A single note, big, with the newer/older notes, a few from the same tag and posts on that topic.
     */
    public function show(int $id): View
    {
        $note = $this->notes->find($id);

        abort_if($note === null, 404);

        [$newer, $older] = $this->notes->neighbours($note);
        $data = $this->notes->toArray($note);

        return view('site.notes.show', [
            'note' => $data,
            'title' => Str::limit($data['text'], 60, '…', preserveWords: true),
            'ogImage' => OgUrl::for('note', (string) $note->id, $note->updated_at),
            'newer' => $newer,
            'older' => $older,
            'sameTag' => $this->notes->sameTag($note),
            'posts' => $this->posts->withTag($note->tag_id),
        ]);
    }
}
