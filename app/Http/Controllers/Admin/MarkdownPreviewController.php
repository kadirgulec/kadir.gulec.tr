<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Section;
use App\Http\Controllers\Controller;
use App\Support\Markdown\Markdown;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

/**
 * Renders Markdown as a small notebook page for the editor's preview iframe,
 * with the site's own stylesheet, so Kadir sees exactly what visitors will.
 */
class MarkdownPreviewController extends Controller
{
    public function __invoke(Request $request, Markdown $markdown): Response
    {
        $validated = $request->validate([
            'markdown' => ['nullable', 'string', 'max:200000'],
            'section' => ['nullable', Rule::enum(Section::class)],
            'dark' => ['boolean'],
        ]);

        return response()->view('admin.markdown-preview', [
            'html' => new HtmlString($markdown->toHtml((string) ($validated['markdown'] ?? ''), 'onizleme')),
            'section' => Section::tryFrom((string) ($validated['section'] ?? '')) ?? Section::Home,
            'dark' => (bool) ($validated['dark'] ?? false),
        ]);
    }
}
