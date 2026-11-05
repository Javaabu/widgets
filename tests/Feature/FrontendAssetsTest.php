<?php

namespace Javaabu\Widgets\Tests\Feature;

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
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());
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
}
