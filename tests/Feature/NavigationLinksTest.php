<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Schutz vor toten Links im Frontend: Jeder in den Vue-Dateien benutzte Routenname und
 * jeder Pfad der Seitenleiste muss im Backend existieren.
 */
class NavigationLinksTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,string> routenname => datei */
    private function usedRouteNames(): array
    {
        $names = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js')));
        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['vue', 'ts'], true)) {
                continue;
            }
            preg_match_all("/route\(\s*'([a-z0-9_.\-]+)'/i", file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $name) {
                $names[$name] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        return $names;
    }

    public function test_every_route_name_used_in_the_frontend_exists(): void
    {
        $used = $this->usedRouteNames();
        $this->assertNotEmpty($used);

        $missing = array_filter($used, fn ($file, $name) => ! Route::has($name), ARRAY_FILTER_USE_BOTH);
        $this->assertSame([], $missing, 'Unbekannte Routen im Frontend: '.json_encode($missing));
    }

    public function test_sidebar_paths_resolve_to_registered_routes(): void
    {
        $sidebar = file_get_contents(resource_path('js/components/AppSidebar.vue'));
        preg_match_all("/href:\s*'(\/[^']*)'/", $sidebar, $m);
        $paths = array_filter($m[1], fn ($p) => ! str_starts_with($p, 'http'));
        $this->assertGreaterThanOrEqual(7, count($paths), 'Sidebar-Einträge nicht gefunden');

        $uris = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => in_array('GET', $r->methods(), true))
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))->all();

        foreach ($paths as $path) {
            $this->assertContains($path, $uris, "Sidebar-Pfad $path ist keine GET-Route");
        }
    }

    public function test_sidebar_component_reads_the_href_field_the_items_provide(): void
    {
        // Regression: NavMain las früher `item.url`, die Einträge liefern aber `href` → leere Links
        $nav = file_get_contents(resource_path('js/components/NavMain.vue'));
        $this->assertStringNotContainsString('item.url', $nav);
        $this->assertStringContainsString('item.href', $nav);
    }

    public function test_settings_are_reachable_from_the_sidebar_and_all_their_pages_exist(): void
    {
        $sidebar = file_get_contents(resource_path('js/components/AppSidebar.vue'));
        $this->assertStringContainsString("href: '/settings/profile'", $sidebar);
        $this->assertStringContainsString("match: '/settings'", $sidebar, 'alle /settings/* Seiten sollen den Menüpunkt aktivieren');

        $user = \App\Models\User::factory()->create();
        foreach (['/settings/profile', '/settings/password', '/settings/appearance'] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
    }
}
