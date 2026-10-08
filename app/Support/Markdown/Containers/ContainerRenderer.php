<?php

namespace App\Support\Markdown\Containers;

use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * Renders review containers: a spoiler crossed out with marker and a quote
 * on a post-it.
 */
final class ContainerRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof Container) {
            return '';
        }

        return $node->kind === 'replik' ? $this->quote($node, $childRenderer) : $this->spoiler($node, $childRenderer);
    }

    private function spoiler(Container $node, ChildNodeRendererInterface $childRenderer): string
    {
        $body = '';

        foreach ($node->children() as $child) {
            $body .= $child instanceof Paragraph
                ? '<p class="leading-8"><span class="spoiler-text">'.$childRenderer->renderNodes($child->children()).'</span></p>'
                : $childRenderer->renderNodes([$child]);
        }

        return '<div data-spoiler class="relative my-8 rounded-sm bg-paper-deep px-5 pt-7 pb-5">'
            .'<span class="stamp absolute -top-3 left-5 -rotate-3 rounded-[3px] border-2 border-pen-red bg-paper px-2 py-0.5 font-mono text-[11px] font-bold tracking-[0.2em] text-pen-red">SPOİLER</span>'
            .'<div class="flex flex-col gap-4">'.$body.'</div>'
            .'<button type="button" data-spoiler-toggle class="mt-3 hidden cursor-pointer font-hand text-xl font-bold text-section-ink underline decoration-wavy decoration-section underline-offset-4 [:where(html.js)_&]:inline-block">yine de okumak istiyorum →</button>'
            .'</div>';
    }

    private function quote(Container $node, ChildNodeRendererInterface $childRenderer): string
    {
        $lines = [];

        foreach ($node->children() as $child) {
            $lines[] = $child instanceof Paragraph ? $childRenderer->renderNodes($child->children()) : strip_tags($childRenderer->renderNodes([$child]));
        }

        $by = $node->info !== '' ? '<figcaption class="mt-3 font-mono text-xs text-[#6b5c3e] dark:text-[#cfc29a]">— '.htmlspecialchars($node->info, ENT_QUOTES).'</figcaption>' : '';

        return '<figure class="relative mx-auto my-10 w-fit max-w-sm rotate-[1.5deg] bg-[#fff1a6] px-6 pt-8 pb-5 shadow-[2px_10px_18px_-10px_rgb(60_40_20/0.55)] transition duration-200 ease-out hover:rotate-0 motion-reduce:transition-none dark:bg-[#4a4322] dark:shadow-[2px_10px_18px_-8px_rgb(0_0_0/0.9)]">'
            .'<span class="tape -top-3 left-1/2 w-20 -translate-x-1/2 -rotate-3"></span>'
            .'<blockquote class="font-hand text-[1.75rem] leading-tight font-bold text-[#3a3020] dark:text-[#f6ecc6]">“'.implode('<br>', $lines).'”</blockquote>'
            .$by
            .'</figure>';
    }
}
