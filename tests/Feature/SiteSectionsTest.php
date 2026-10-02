<?php

use App\Enums\Section;

it('renders each section page in its own section color', function (Section $section, string $path) {
    $response = $this->get($path);

    $response->assertSee('<body data-section="'.$section->value.'"', false);
})->with([
    'home' => [Section::Home, '/'],
    'posts' => [Section::Posts, '/yazilar'],
    'watched' => [Section::Watched, '/izlediklerim'],
    'goals' => [Section::Goals, '/hedefler'],
    'projects' => [Section::Projects, '/projeler'],
    'about' => [Section::About, '/hakkimda'],
]);

it('marks only the visited section as the current tab', function () {
    $response = $this->get('/hedefler');

    $html = $response->getContent();

    // Two navs (desktop tabs and mobile bar), each with exactly one current link.
    expect(substr_count($html, 'aria-current="page"'))->toBe(2)
        ->and(preg_match_all('#href="'.preg_quote(route('goals.index'), '#').'"\s+aria-current="page"#', $html))->toBe(2);
});

it('names the page after its section', function () {
    $response = $this->get('/izlediklerim');

    $response->assertSee('<title>İzlediklerim · Kadir Gülec</title>', false);
});
