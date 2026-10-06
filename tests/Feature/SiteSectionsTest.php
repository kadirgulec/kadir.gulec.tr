<?php

use App\Enums\Section;

it('renders each section page in its own section color', function (Section $section, string $path) {
    $response = $this->get($path);

    expect($response->getContent())->toMatch('/<body\s+data-section="'.$section->value.'"/');
})->with([
    'home' => [Section::Home, '/'],
    'posts' => [Section::Posts, '/yazilar'],
    'notes' => [Section::Notes, '/ogrendiklerim'],
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

it('leaves home out of the mobile bar and offers the corner stamp instead', function () {
    $html = $this->get('/')->getContent();

    // Only the desktop tab marks home as current; the mobile bar has no home tab.
    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toContain('data-home-stamp');
});

it('leaves about out of the mobile bar and links it from the footer', function () {
    $html = $this->get('/hakkimda')->getContent();

    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toContain('>hakkımda</a>');
});

it('names the page after its section', function () {
    $response = $this->get('/izlediklerim');

    $response->assertSee('<title>İzlediklerim · Kadir Gülec</title>', false);
});
