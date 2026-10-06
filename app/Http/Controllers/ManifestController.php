<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The web app manifest that makes the notebook installable (with public/sw.js).
 * A route rather than a static file, so it carries its own content type.
 */
class ManifestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'id' => '/',
            'name' => 'Kadir Gülec',
            'short_name' => 'kg',
            'description' => "Kadir Gülec'in dijital defteri: yazılar, izledikleri, hedefleri ve projeleri.",
            'lang' => 'tr',
            'dir' => 'ltr',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#fbf7ee',
            'theme_color' => '#fbf7ee',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => $this->shortcuts($request),
        ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'private, no-cache');
    }

    /**
     * Long-press menu of the home screen icon. Whoever writes notes gets
     * "Yeni not" first; the manifest is requested with credentials for that
     * (see components/pwa-head), and it differs per person, hence private.
     *
     * @return list<array{name: string, url: string}>
     */
    private function shortcuts(Request $request): array
    {
        $shortcuts = [
            ['name' => 'Öğrendiklerim', 'url' => route('notes.index', absolute: false)],
            ['name' => 'Hedefler', 'url' => route('goals.index', absolute: false)],
            ['name' => 'Yazılar', 'url' => route('posts.index', absolute: false)],
            ['name' => 'İzlediklerim', 'url' => route('watched.index', absolute: false)],
        ];

        if ($request->user()?->can(Permission::ManageNotes->value)) {
            array_unshift($shortcuts, ['name' => 'Yeni not', 'url' => route('admin.notes.create', absolute: false)]);
        }

        return $shortcuts;
    }
}
