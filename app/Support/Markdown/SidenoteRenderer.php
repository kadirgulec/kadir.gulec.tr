<?php

namespace App\Support\Markdown;

use League\CommonMark\Extension\Footnote\Node\Footnote;
use League\CommonMark\Extension\Footnote\Node\FootnoteBackref;
use League\CommonMark\Extension\Footnote\Node\FootnoteRef;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Renders a footnote reference ([^1]) as a Tufte sidenote right where it is
 * referenced: on wide screens the note sits in the right margin, on small
 * screens the number is a label that toggles it (pure CSS, see site.css).
 * The footnote definitions themselves render nothing (FootnoteContainer is
 * mapped to an empty renderer).
 */
final class SidenoteRenderer implements NodeRendererInterface
{
    public function __construct(private string $idPrefix) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FootnoteRef) {
            return '';
        }

        $label = $node->getReference()->getLabel();
        $number = $node->getReference()->getTitle();
        $footnote = $this->findFootnote($node, $label);

        if ($footnote === null) {
            return '';
        }

        $id = htmlspecialchars($this->idPrefix.'-'.$label, ENT_QUOTES);
        $key = htmlspecialchars($number, ENT_QUOTES);

        return '<label for="'.$id.'" class="sidenote-number">'.$key.'</label>'
            .'<input type="checkbox" id="'.$id.'" class="margin-toggle">'
            .'<span class="sidenote"><span class="sidenote-key">'.$key.'</span> '.$this->inlineContent($footnote, $childRenderer).'</span>';
    }

    private function findFootnote(Node $node, string $label): ?Footnote
    {
        $document = $node;

        while ($document->parent() !== null) {
            $document = $document->parent();
        }

        foreach ($document->iterator() as $candidate) {
            if ($candidate instanceof Footnote && $candidate->getReference()->getLabel() === $label) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The note's paragraphs, flattened to inline markup (a sidenote lives inside a <p>).
     */
    private function inlineContent(Footnote $footnote, ChildNodeRendererInterface $childRenderer): string
    {
        $parts = [];

        foreach ($footnote->children() as $child) {
            if ($child instanceof Paragraph) {
                $inline = array_filter(
                    iterator_to_array($child->children(), false),
                    fn (Node $node): bool => ! $node instanceof FootnoteBackref,
                );
                $parts[] = $childRenderer->renderNodes($inline);
            }
        }

        return trim(implode(' ', $parts));
    }
}
