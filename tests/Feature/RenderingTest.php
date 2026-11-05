<?php

namespace Javaabu\Widgets\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Tests\TestCase;
use Javaabu\Widgets\Tests\TestSupport\Widgets\CounterWidget;
use Javaabu\Widgets\Widgets\TextWidget;
use Javaabu\Widgets\WidgetsManager;
use PHPUnit\Framework\Attributes\Test;

class RenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Widgets::register(CounterWidget::class);
        Widgets::location('sidebar', 'Sidebar');
    }

    protected function add(string $type, array $settings, string $location = 'sidebar', int $order = 0): WidgetInstance
    {
        return WidgetInstance::create([
            'location' => $location,
            'type' => $type,
            'settings' => $settings,
            'order' => $order,
        ]);
    }

    #[Test]
    public function it_renders_a_location_in_order_with_the_blade_component(): void
    {
        $second = $this->add('text', ['title' => 'ދެވަނަ', 'text' => 'Second'], order: 2);
        $first = $this->add('text', ['title' => 'ފުރަތަމަ', 'text' => "Line one\nLine two"], order: 1);
        $this->add('text', ['text' => 'Elsewhere'], location: 'footer');

        $html = Blade::render('<x-widgets::location name="sidebar" class="space-y-4" />');

        $this->assertStringContainsString('<div class="widgets widgets-sidebar space-y-4" data-widget-location="sidebar">', $html);
        $this->assertStringContainsString('<section class="widget widget-text" id="widget-' . $first->id . '" data-widget="text">', $html);
        $this->assertStringContainsString('<h2 class="widget-title">ފުރަތަމަ</h2>', $html);
        $this->assertStringContainsString("<div class=\"widget-text\">Line one\nLine two</div>", $html);
        $this->assertLessThan(strpos($html, 'widget-' . $second->id), strpos($html, 'widget-' . $first->id));
        $this->assertStringNotContainsString('Elsewhere', $html);
    }

    #[Test]
    public function it_renders_nothing_for_an_empty_location(): void
    {
        $this->assertSame('', trim(Blade::render('<x-widgets::location name="sidebar" />')));
        $this->assertStringContainsString('data-widget-location="sidebar"', Blade::render('<x-widgets::location name="sidebar" always />'));
        $this->assertFalse(Widgets::has('sidebar'));
        $this->assertSame('', Widgets::render('sidebar')->toHtml());
    }

    #[Test]
    public function it_escapes_settings(): void
    {
        $this->add('text', ['text' => '<script>alert(1)</script>']);

        $html = Widgets::render('sidebar')->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function it_passes_data_and_defaults_to_the_view(): void
    {
        $this->add('counter', ['label' => 'ގިނަ']);

        $this->assertStringContainsString('<p class="counter">ގިނަ: 3 / 6</p>', Widgets::render('sidebar')->toHtml());
    }

    #[Test]
    public function it_skips_widgets_whose_type_is_gone_or_that_have_nothing_to_show(): void
    {
        $this->add('removed', ['text' => 'Gone']);
        $this->add('text', ['text' => '']);
        $this->add('image', ['src' => 'javascript:alert(1)']);
        $this->add('link', ['label' => 'Go', 'url' => 'javascript:alert(1)']);

        $this->assertCount(4, Widgets::widgets('sidebar'));
        $this->assertCount(0, Widgets::visible('sidebar'));
        $this->assertFalse(Widgets::has('sidebar'));
        $this->assertSame('', Widgets::renderWidget(Widgets::widgets('sidebar')->first())->toHtml());
    }

    #[Test]
    public function it_renders_the_image_widget(): void
    {
        $this->add('image', ['src' => 'https://example.com/a.png', 'alt' => 'ރާއްޖެ', 'caption' => 'Malé', 'link' => '/about']);
        $this->add('image', ['src' => '/b.png'], order: 1);

        $html = Widgets::render('sidebar')->toHtml();

        $this->assertStringContainsString('<a href="/about"><img src="https://example.com/a.png" alt="ރާއްޖެ" loading="lazy"></a>', $html);
        $this->assertStringContainsString('<figcaption>Malé</figcaption>', $html);
        $this->assertStringContainsString('<img src="/b.png" alt="" loading="lazy">', $html);
    }

    #[Test]
    public function it_renders_the_link_widget(): void
    {
        $this->add('link', ['title' => 'ގުޅުއްވާ', 'description' => 'Get in touch', 'label' => 'Contact', 'url' => 'https://example.com']);
        $this->add('link', ['label' => 'About', 'url' => '/about', 'new_tab' => true], order: 1);

        $html = Widgets::render('sidebar')->toHtml();

        $this->assertStringContainsString('<p class="widget-description">Get in touch</p>', $html);
        $this->assertStringContainsString('<a class="widget-button" href="https://example.com" rel="noopener">Contact</a>', $html);
        $this->assertStringContainsString('<a class="widget-button" href="/about" target="_blank" rel="noopener noreferrer">About</a>', $html);
    }

    #[Test]
    public function it_uses_the_locations_wrapper(): void
    {
        $this->app['view']->addNamespace('custom', __DIR__ . '/../TestSupport/views');
        file_put_contents(__DIR__ . '/../TestSupport/views/wrapper.blade.php', '<aside data-id="{{ $widget->id }}" data-location="{{ $location->getKey() }}">{{ $slot }}</aside>');

        try {
            Widgets::location('sidebar')->wrapper('custom::wrapper');
            $widget = $this->add('text', ['text' => 'Hi']);

            $this->assertStringContainsString('<aside data-id="' . $widget->id . '" data-location="sidebar"><div class="widget-text">Hi</div></aside>', Widgets::render('sidebar')->toHtml());
        } finally {
            unlink(__DIR__ . '/../TestSupport/views/wrapper.blade.php');
        }
    }

    #[Test]
    public function it_serializes_widgets_for_react(): void
    {
        $widget = $this->add('counter', ['label' => 'ގިނަ', 'count' => 4]);
        $this->add('text', ['text' => 'Footer'], location: 'footer');

        $this->assertEquals([[
            'id' => $widget->id,
            'type' => 'counter',
            'component' => 'Counter',
            'location' => 'sidebar',
            'settings' => ['label' => 'ގިނަ', 'count' => 4],
            'data' => (object) ['doubled' => 8],
        ]], Widgets::toArray('sidebar'));

        $props = Widgets::props();

        $this->assertEquals(['sidebar'], array_keys($props));
        $this->assertCount(1, Widgets::props(['footer'])['footer']);
    }

    #[Test]
    public function it_loads_a_location_once_per_request(): void
    {
        $this->add('text', ['text' => 'Hi']);

        DB::enableQueryLog();

        Widgets::render('sidebar');
        Widgets::render('sidebar');
        Widgets::has('sidebar');

        $this->assertCount(1, DB::getQueryLog());
    }

    #[Test]
    public function it_reloads_after_a_widget_changes(): void
    {
        $widget = $this->add('text', ['text' => 'Before']);

        $this->assertStringContainsString('Before', Widgets::render('sidebar')->toHtml());

        $widget->update(['settings' => ['text' => 'After']]);

        $this->assertStringContainsString('After', Widgets::render('sidebar')->toHtml());

        $widget->delete();

        $this->assertFalse(Widgets::has('sidebar'));
    }

    #[Test]
    public function it_caches_locations_between_requests_when_enabled(): void
    {
        $this->app['config']->set('widgets.cache.enabled', true);

        $widget = $this->add('text', ['text' => 'Cached']);

        Widgets::render('sidebar');

        // Change it behind the cache's back, and load it as a new request would
        DB::table('widget_instances')->where('id', $widget->id)->update(['settings' => json_encode(['text' => 'Changed'])]);

        $manager = new WidgetsManager($this->app);
        $manager->register(TextWidget::class);

        DB::enableQueryLog();
        $html = $manager->render('sidebar')->toHtml();

        $this->assertStringContainsString('Cached', $html);
        $this->assertCount(0, DB::getQueryLog());

        $widget->refresh()->update(['settings' => ['text' => 'Saved']]);

        $this->assertStringContainsString('Saved', Widgets::render('sidebar')->toHtml());
    }
}
