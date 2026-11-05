<?php

namespace Javaabu\Widgets\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The built-in widgets must render the same markup in Blade and React. This
 * test writes (with WIDGETS_UPDATE_FIXTURES=1) or checks tests/Fixtures/rendering:
 * the widgets as React gets them, and the HTML Blade renders. tests/js/fixtures.test.tsx
 * renders the same widgets with <WidgetLocation> and compares.
 */
class RenderingFixturesTest extends TestCase
{
    /**
     * @return array<string, array{array<int, array{string, array<string, mixed>}>}>
     */
    public static function fixtures(): array
    {
        return [
            'text' => [[
                ['text', ['title' => 'މަރުޙަބާ', 'text' => "ފުރަތަމަ ބައި\nދެވަނަ ބައި"]],
                ['text', ['text' => 'Only text, with <b>tags</b> & "quotes" it\'s']],
            ]],
            'image' => [[
                ['image', ['title' => 'Malé', 'src' => 'https://example.com/male.jpg', 'alt' => 'މާލެ', 'caption' => 'ރާއްޖޭގެ ވެރިރަށް', 'link' => '/male']],
                ['image', ['src' => '/images/a.png']],
            ]],
            'link' => [[
                ['link', ['title' => 'ގުޅުއްވާ', 'description' => 'Get in touch', 'label' => 'ގުޅުއްވާ', 'url' => 'https://example.com/contact']],
                ['link', ['label' => 'About', 'url' => '/about', 'new_tab' => true]],
                ['link', ['label' => 'Mail', 'url' => 'mailto:info@example.com']],
            ]],
        ];
    }

    /**
     * @param  array<int, array{string, array<string, mixed>}>  $widgets
     */
    #[Test]
    #[DataProvider('fixtures')]
    public function it_matches_the_rendering_fixture(array $widgets): void
    {
        Widgets::location('sidebar', 'Sidebar');

        foreach ($widgets as $order => [$type, $settings]) {
            WidgetInstance::create(['id' => $order + 1, 'location' => 'sidebar', 'type' => $type, 'settings' => $settings, 'order' => $order]);
        }

        $fixture = [
            'widgets' => Widgets::toArray('sidebar'),
            'html' => trim(Blade::render('<x-widgets::location name="sidebar" />')),
        ];

        $name = $this->dataName();
        $path = dirname(__DIR__) . "/Fixtures/rendering/{$name}.json";
        $json = json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        if (getenv('WIDGETS_UPDATE_FIXTURES')) {
            file_put_contents($path, $json);
        }

        $this->assertFileExists($path, 'Run WIDGETS_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter RenderingFixturesTest');
        $this->assertJsonStringEqualsJsonString(file_get_contents($path), $json);
    }
}
