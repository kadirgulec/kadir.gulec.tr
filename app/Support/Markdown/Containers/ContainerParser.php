<?php

namespace App\Support\Markdown\Containers;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

/**
 * Keeps a container open until a line with only ":::"; everything inside is
 * parsed as Markdown (paragraphs, emphasis, …).
 */
final class ContainerParser extends AbstractBlockContinueParser
{
    private Container $block;

    public function __construct(string $kind, string $info)
    {
        $this->block = new Container($kind, $info);
    }

    public function getBlock(): Container
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return ! $childBlock instanceof Container;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): BlockContinue
    {
        if (! $cursor->isIndented() && preg_match('/^:::[ \t]*$/', $cursor->getRemainder())) {
            $cursor->advanceToEnd();

            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }
}
