<?php

namespace App\Support\Markdown\Containers;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * Opens a container on ":::spoiler" or ":::replik Kişi".
 */
final class ContainerStartParser implements BlockStartParserInterface
{
    public const KINDS = ['spoiler', 'replik'];

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $pattern = '/^:::[ \t]*('.implode('|', self::KINDS).')(?:[ \t]+(.*))?$/u';

        if (! preg_match($pattern, $cursor->getRemainder(), $match)) {
            return BlockStart::none();
        }

        $cursor->advanceToEnd();

        return BlockStart::of(new ContainerParser($match[1], trim($match[2] ?? '')))->at($cursor);
    }
}
