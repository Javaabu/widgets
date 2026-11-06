<?php

namespace Javaabu\Widgets\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Javaabu\Manifest\Manifest;
use Javaabu\Widgets\Assets\Assets;
use Javaabu\Widgets\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    #[Test]
    public function it_registers_the_assets_with_manifest(): void
    {
        $manifest = $this->app->make(Manifest::class);

        $this->assertTrue($manifest->hasPackage(Assets::PACKAGE));
        $this->assertTrue($manifest->has(Assets::STYLES));
        $this->assertTrue($manifest->has(Assets::ADMIN));

        $package = $manifest->package(Assets::PACKAGE);

        $this->assertEquals('@javaabu/widgets', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertFileExists($manifest->entryPath($manifest->find(Assets::ADMIN)));
        $this->assertFileExists(dirname(__DIR__, 2) . '/dist/.vite/manifest.json');
    }

    #[Test]
    public function it_ships_both_inertia_pages(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/js/Pages/Widgets';

        $this->assertFileExists("{$root}/Index.tsx");
        $this->assertFileExists("{$root}/Edit.tsx");
    }

    /**
     * The npm packages manifest:check asks for on widgets' behalf, by scope
     *
     * @return array<string, array<int, string>> package => scopes
     */
    protected function checkedFor(string ...$scopes): array
    {
        $options = ['--json' => true, '--scope' => $scopes];

        Artisan::call('manifest:check', $options);

        $rows = json_decode(Artisan::output(), true);
        $needed = [];

        foreach ($rows as $row) {
            if (in_array(Assets::PACKAGE, $row['needed_by'], true)) {
                $needed[$row['name']] = $row['scopes'];
            }
        }

        return $needed;
    }

    #[Test]
    public function blade_apps_arent_asked_for_react_packages(): void
    {
        // Only the build tools manifest asks for whenever a package has assets
        $this->assertEqualsCanonicalizing(['laravel-vite-plugin', 'vite'], array_keys($this->checkedFor('assets')));
    }

    #[Test]
    public function inertia_apps_are_asked_for_what_the_pages_import(): void
    {
        $needed = $this->checkedFor('pages');

        foreach (['@javaabu/forms', '@javaabu/onu', 'react', 'react-dom', '@inertiajs/react'] as $name) {
            $this->assertArrayHasKey($name, $needed);
            $this->assertEquals(['pages'], $needed[$name]);
        }
    }

    #[Test]
    public function the_blade_script_imports_nothing_from_npm(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/js';

        foreach (['blade/admin.ts', 'sortable.ts'] as $file) {
            preg_match_all('/^\s*import\s.*?from\s+["\']([^"\']+)["\']|^\s*import\s+["\']([^"\']+)["\']/m', file_get_contents("{$root}/{$file}"), $matches);

            foreach (array_filter([...$matches[1], ...$matches[2]]) as $import) {
                $this->assertStringStartsWith('.', $import, "{$file} imports {$import}: declare it in the assets scope or keep the Blade script free of npm imports.");
            }
        }
    }

    #[Test]
    public function the_react_widget_components_import_their_styles(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/js';

        foreach (['components/WidgetLocation.tsx', 'widgets/TextWidget.tsx', 'widgets/ImageWidget.tsx', 'widgets/LinkWidget.tsx'] as $file) {
            $this->assertStringContainsString('import "../../css/widgets.css"', file_get_contents("{$root}/{$file}"), "{$file} must import the widget styles, so Inertia apps get them.");
        }
    }
}
