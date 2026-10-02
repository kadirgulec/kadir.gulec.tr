<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Markdown\Markdown;
use Illuminate\Http\Response;

/**
 * Atom feed of the posts with their full text.
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
}
