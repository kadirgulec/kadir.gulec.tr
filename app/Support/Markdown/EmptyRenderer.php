<?php

namespace App\Support\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Renders nothing: for nodes whose content is shown elsewhere (footnotes
 * become sidenotes).
 */
final class EmptyRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        return '';
    }
}
