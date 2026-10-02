<?php

namespace App\Support\Markdown;

use App\Support\Images\ImageStore;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\Block\ParagraphRenderer;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * A paragraph that holds nothing but an image becomes a taped polaroid:
 * ![alt text](/storage/posts/…-960.webp "caption"). Images from the image
 * store get a srcset; only http(s) and site paths are allowed as sources.
 * Every other paragraph renders as usual.
 */
final class PolaroidRenderer implements NodeRendererInterface
{
    public function __construct(private ParagraphRenderer $paragraphs = new ParagraphRenderer) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string|null
    {
        if (! $node instanceof Paragraph) {
            return '';
        }

        $image = $this->onlyImage($node);

        if ($image === null) {
            return $this->paragraphs->render($node, $childRenderer);
        }

        $url = $image->getUrl();

        if (! preg_match('#^(https?://|/(?!/))#i', $url)) {
            return '';
        }

        $alt = htmlspecialchars($this->altText($image), ENT_QUOTES);
        $caption = trim((string) $image->getTitle());
        $srcset = '';

        if (preg_match('#^/storage/(.+)-(?:480|960|1600)\.webp$#', $url, $match)) {
            $srcset = ' srcset="'.htmlspecialchars((string) ImageStore::srcset($match[1]), ENT_QUOTES).'" sizes="(min-width: 48rem) 40rem, 100vw"';
        }

        return '<figure class="polaroid">'
            .'<img src="'.htmlspecialchars($url, ENT_QUOTES).'"'.$srcset.' alt="'.$alt.'" loading="lazy">'
            .($caption !== '' ? '<figcaption>'.htmlspecialchars($caption, ENT_QUOTES).'</figcaption>' : '')
            .'</figure>';
    }

    private function onlyImage(Paragraph $paragraph): ?Image
    {
        $children = array_values(array_filter(
            iterator_to_array($paragraph->children(), false),
            fn (Node $child): bool => ! ($child instanceof Text && trim($child->getLiteral()) === ''),
        ));

        return count($children) === 1 && $children[0] instanceof Image ? $children[0] : null;
    }

    private function altText(Image $image): string
    {
        $text = '';

        foreach ($image->iterator() as $child) {
            if ($child instanceof Text) {
                $text .= $child->getLiteral();
            }
        }

        return $text;
    }
}
