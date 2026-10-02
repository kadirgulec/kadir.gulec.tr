<?php

namespace App\Support\Markdown\Containers;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * ":::spoiler … :::" and ":::replik Kişi … :::" blocks for film reviews.
 */
final class ContainerExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(new ContainerStartParser, 80);
        $environment->addRenderer(Container::class, new ContainerRenderer);
    }
}
