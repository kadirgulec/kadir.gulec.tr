<?php

namespace Database\Factories;

use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => '/yazilar/'.fake()->slug(3),
            'referrer_host' => null,
            'utm_source' => null,
            'visitor_hash' => fake()->regexify('[0-9a-f]{16}'),
            'browser' => 'Firefox',
            'os' => 'Linux',
            'device' => 'desktop',
        ];
    }
}
