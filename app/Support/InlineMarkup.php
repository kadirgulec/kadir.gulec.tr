<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Turns the small inline syntax used in post bodies into HTML:
 *
 *   ==text==   highlighter pen
 *   **text**   bold
 *   `text`     inline code
 *   [^key]     sidenote, its text comes from $notes[key]
 *
 * The text is escaped first, so nothing the author types can inject markup;
 * only the tokens above produce tags.
 */
class InlineMarkup
{
    /**
     * @param  array<int|string, string>  $notes  Numeric keys ("1") become integers in PHP arrays; both work.
     */
    public static function render(string $text, array $notes = [], string $idPrefix = 'not'): HtmlString
    {
        $html = self::basic(e($text));

        $html = preg_replace_callback('/\[\^([A-Za-z0-9_-]+)\]/', function (array $match) use ($notes, $idPrefix): string {
            $key = $match[1];

            if (! isset($notes[$key])) {
                return '';
            }

            $id = e($idPrefix.'-'.$key);
            $note = self::basic(e($notes[$key]));

            return '<label for="'.$id.'" class="sidenote-number">'.e($key).'</label>'
                .'<input type="checkbox" id="'.$id.'" class="margin-toggle">'
                .'<span class="sidenote"><span class="sidenote-key">'.e($key).'</span> '.$note.'</span>';
        }, $html) ?? $html;

        return new HtmlString($html);
    }

    /**
     * Highlight, bold and inline code on already escaped text.
     */
    private static function basic(string $escaped): string
    {
        return preg_replace(
            ['/==(.+?)==/u', '/\*\*(.+?)\*\*/u', '/`(.+?)`/u'],
            ['<mark class="marker">$1</mark>', '<strong>$1</strong>', '<code class="inline-code">$1</code>'],
            $escaped,
        ) ?? $escaped;
    }
}
