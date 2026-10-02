<?php

it('shows the rating with a Turkish decimal comma', function (float $rating, string $expected) {
    $view = $this->blade('<x-site.grade :value="$rating" />', ['rating' => $rating]);

    $view->assertSeeText($expected);
})->with([
    'half point' => [8.5, '8,5'],
    'whole point drops the decimals' => [9.0, '9'],
]);
