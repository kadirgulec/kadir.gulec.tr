<?php

namespace App\Support;

/**
 * Comments are plain text: line breaks and links work, nothing else.
 * The text is escaped first; only the links and breaks become markup.
 * Links open in a new tab and get rel="nofollow ugc noopener", so they pass no search ranking.
 */
class CommentFormatter
{
    public static function toHtml(string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($text)) ?: [];

        return implode('', array_map(function (string $paragraph): string {
            $html = self::linkify(e(trim($paragraph)));

            return '<p>'.nl2br($html, false).'</p>';
        }, array_filter($paragraphs, fn (string $paragraph): bool => trim($paragraph) !== '')));
    }

    private static function linkify(string $escaped): string
    {
        return preg_replace_callback(
            '#\bhttps?://[^\s<>"\']+#iu',
            function (array $match): string {
                $url = rtrim($match[0], '.,;:!?)');
                $rest = substr($match[0], strlen($url));

                return '<a href="'.$url.'" target="_blank" rel="nofollow ugc noopener" class="underline decoration-section decoration-2 underline-offset-4">'.$url.'</a>'.$rest;
            },
            $escaped,
        ) ?? $escaped;
    }
}
