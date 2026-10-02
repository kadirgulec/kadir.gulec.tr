<?php

namespace App\Support\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tempest\Highlight\Highlighter;

/**
 * A fenced code block as the notebook's dark card (the same markup as the
 * x-site.code-block component): tape, language label, copy button and
 * syntax colors from tempest/highlight (hl-* classes, see site.css).
 */
final class CodeBlockRenderer implements NodeRendererInterface
{
    /**
     * Languages tempest/highlight colors; anything else is shown as plain text.
     */
    private const LANGUAGES = ['php', 'blade', 'html', 'xml', 'css', 'js', 'javascript', 'ts', 'typescript', 'json', 'sql', 'yaml', 'yml', 'bash', 'sh', 'shell', 'diff', 'twig', 'dockerfile', 'ini', 'python', 'py', 'markdown', 'md', 'txt'];

    public function __construct(private Highlighter $highlighter = new Highlighter) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode && ! $node instanceof IndentedCode) {
            return '';
        }

        $language = $node instanceof FencedCode ? strtolower(trim($node->getInfoWords()[0] ?? '')) : '';
        $language = preg_replace('/[^a-z0-9+#-]/', '', $language) ?? '';
        $code = rtrim($node->getLiteral(), "\n");

        $highlighted = in_array($language, self::LANGUAGES, true)
            ? $this->highlighter->parse($code, $language)
            : htmlspecialchars($code, ENT_QUOTES);

        $label = htmlspecialchars($language !== '' ? $language : 'metin', ENT_QUOTES);

        return '<figure class="code-card" data-code-block>'
            .'<span class="tape -top-3 left-6 w-20 -rotate-6"></span>'
            .'<figcaption class="code-card-caption">'
            .'<span class="code-card-lang">'.$label.'</span>'
            .'<button type="button" data-copy class="code-card-copy">kopyala</button>'
            .'</figcaption>'
            .'<pre class="code-card-pre"><code>'.$highlighted.'</code></pre>'
            .'</figure>';
    }
}
