<?php

namespace App\Support\Markdown;

use App\Support\Markdown\Containers\ContainerExtension;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;
use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Extension\Highlight\HighlightExtension;
use League\CommonMark\Extension\Highlight\Mark;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;

/**
 * The Markdown dialect of the notebook. Standard Markdown plus:
 *
 *   [^1]       sidenote (the footnote text appears in the margin)
 *   ==text==   highlighter pen
 *   ~~text~~   strikethrough
 *   ```php     code card with syntax colors (tempest/highlight)
 *   ![alt](src "caption")  on its own line: a taped polaroid
 *   :::spoiler … :::         a spoiler crossed out with marker
 *   :::replik Kişi … :::     a favorite line on a post-it
 *
 * Links to other sites open in a new tab. Raw HTML is escaped and unsafe links are dropped, so nothing typed into a
 * text field can inject markup. HTML is produced once when a model is saved
 * (see RendersMarkdown), never on page views.
 */
#[Singleton]
class Markdown
{
    /**
     * The last built converter and its "idPrefix|sidenotes" key: the environment
     * and its renderers keep no per-document state, so repeated calls with the
     * same options reuse it. Only one is kept, as notes get a prefix per document.
     *
     * @var array{0: string, 1: MarkdownConverter}|null
     */
    private ?array $converter = null;

    public function toHtml(string $markdown, string $idPrefix = 'not'): string
    {
        return trim($this->converter($idPrefix)->convert($markdown)->getContent());
    }

    /**
     * HTML for feed readers: sidenotes become plain footnotes at the end,
     * since readers drop the CSS that puts them in the margin.
     */
    public function toFeedHtml(string $markdown): string
    {
        return trim($this->converter('not', sidenotes: false)->convert($markdown)->getContent());
    }

    /**
     * Plain text of the rendered Markdown, for excerpts and reading time.
     */
    public function toText(string $markdown): string
    {
        return $this->plainText($this->toHtml($markdown));
    }

    /**
     * Rendered HTML without tags and without the sidenotes. Stored HTML columns
     * go through here directly, so a page view needs no second render.
     */
    public function plainText(string $html): string
    {
        // A sidenote holds its key span and inline markup, never another span.
        $html = preg_replace('/<span class="sidenote"><span class="sidenote-key">.*?<\/span>.*?<\/span>/s', '', $html) ?? $html;
        $html = preg_replace('/<label[^>]*class="sidenote-number"[^>]*>.*?<\/label>/s', '', $html) ?? $html;
        $html = str_replace(['</p>', '</li>', '</h2>', '</h3>'], ["</p>\n", "</li>\n", "</h2>\n", "</h3>\n"], $html);

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
    }

    /**
     * The first paragraph, shortened on a word boundary (~160 characters).
     * The whole text is rendered, so footnote references resolve.
     */
    public function excerpt(string $markdown, int $limit = 160): string
    {
        return $this->htmlExcerpt($this->toHtml($markdown), $limit);
    }

    /**
     * The excerpt of already rendered HTML.
     */
    public function htmlExcerpt(string $html, int $limit = 160): string
    {
        preg_match('/<p>(.*?)<\/p>/s', $html, $match);

        return Str::limit($this->plainText($match[1] ?? ''), $limit, '…', preserveWords: true);
    }

    /**
     * Minutes at 200 words per minute, at least one.
     */
    public function readingMinutes(string $markdown): int
    {
        return max(1, (int) ceil(str_word_count($this->toText($markdown), 0, 'çğıöşüÇĞİÖŞÜâîû') / 200));
    }

    private function converter(string $idPrefix, bool $sidenotes = true): MarkdownConverter
    {
        $key = $idPrefix.'|'.(int) $sidenotes;

        if ($this->converter === null || $this->converter[0] !== $key) {
            $this->converter = [$key, $this->makeConverter($idPrefix, $sidenotes)];
        }

        return $this->converter[1];
    }

    private function makeConverter(string $idPrefix, bool $sidenotes): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'],
                'open_in_new_window' => true,
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => '',
            ],
            'default_attributes' => [
                Code::class => ['class' => 'inline-code'],
                Mark::class => ['class' => 'marker'],
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new HighlightExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new DefaultAttributesExtension);
        $environment->addExtension(new FootnoteExtension);
        $environment->addExtension(new ContainerExtension);

        $environment->addRenderer(FencedCode::class, new CodeBlockRenderer, 10);
        $environment->addRenderer(IndentedCode::class, new CodeBlockRenderer, 10);
        $environment->addRenderer(Paragraph::class, new PolaroidRenderer, 10);

        if ($sidenotes) {
            $environment->addRenderer(FootnoteRef::class, new SidenoteRenderer($idPrefix), 10);
            $environment->addRenderer(FootnoteContainer::class, new EmptyRenderer, 10);
        }

        return new MarkdownConverter($environment);
    }
}
