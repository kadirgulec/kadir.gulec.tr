<?php

namespace App\Models\Concerns;

use App\Support\Markdown\Markdown;
use Illuminate\Database\Eloquent\Model;

/**
 * Renders Markdown columns into their HTML columns whenever the model is
 * saved, so page views only print stored HTML.
 *
 * The model lists its pairs in markdownColumns(): ['body' => 'body_html'].
 */
trait RendersMarkdown
{
    /**
     * @return array<string, string> Markdown column => HTML column
     */
    abstract protected function markdownColumns(): array;

    public static function bootRendersMarkdown(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $model->renderMarkdownColumns();
        });
    }

    public function renderMarkdownColumns(bool $force = false): void
    {
        $markdown = app(Markdown::class);

        foreach ($this->markdownColumns() as $source => $target) {
            if (! $force && ! $this->isDirty($source) && $this->getAttribute($target) !== null) {
                continue;
            }

            $text = $this->getAttribute($source);

            $this->setAttribute($target, filled($text)
                ? $markdown->toHtml((string) $text, 'not-'.substr(md5($this->getTable().$source.$text), 0, 8))
                : null);
        }
    }
}
