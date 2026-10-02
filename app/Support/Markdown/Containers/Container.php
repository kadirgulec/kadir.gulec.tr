<?php

namespace App\Support\Markdown\Containers;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * A ":::kind info … :::" block of a film review: a spoiler or a quote.
 */
final class Container extends AbstractBlock
{
    public function __construct(public readonly string $kind, public readonly string $info)
    {
        parent::__construct();
    }
}
