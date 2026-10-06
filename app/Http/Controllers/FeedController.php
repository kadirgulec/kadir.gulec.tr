<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Post;
use App\Support\Markdown\Markdown;
use Illuminate\Http\Response;

/**
 * Atom feeds with the full text: the posts, and the notes on their own.
 */
class FeedController extends Controller
{
    private const LIMIT = 30;

    public function posts(Markdown $markdown): Response
    {
        $posts = Post::query()->published()->with('tags')->orderByDesc('published_at')->orderByDesc('id')->limit(self::LIMIT)->get();

        return response()
            ->view('feeds.posts', [
                'posts' => $posts,
                'updated' => $posts->max('updated_at') ?? now(),
                'markdown' => $markdown,
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8');
    }

    /**
     * Notes have no title; an entry is titled with the start of its text, as feed readers do anyway.
     */
    public function notes(Markdown $markdown): Response
    {
        $notes = Note::query()->published()->with('tag')->orderByDesc('published_at')->orderByDesc('id')->limit(self::LIMIT)->get();

        return response()
            ->view('feeds.notes', [
                'notes' => $notes,
                'updated' => $notes->max('updated_at') ?? now(),
                'markdown' => $markdown,
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8');
    }
}
