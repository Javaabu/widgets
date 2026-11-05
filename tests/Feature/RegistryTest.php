<?php

namespace Javaabu\Widgets\Tests\Feature;

use Javaabu\Widgets\Exceptions\WidgetsException;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Tests\TestCase;
use Javaabu\Widgets\Tests\TestSupport\Widgets\CounterWidget;
use Javaabu\Widgets\Widgets\ImageWidget;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class RegistryTest extends TestCase
{
    #[Test]
    public function it_registers_the_built_in_widgets_from_config(): void
    {
        $this->assertEquals(['text', 'image', 'link'], array_keys(Widgets::all()));
        $this->assertInstanceOf(TextWidget::class, Widgets::find('text'));
        $this->assertInstanceOf(ImageWidget::class, Widgets::find('image'));
        $this->assertInstanceOf(LinkWidget::class, Widgets::find('link'));
    }

    #[Test]
    public function it_registers_and_unregisters_widgets(): void
    {
        Widgets::register(CounterWidget::class);

        $this->assertTrue(Widgets::exists('counter'));
        $this->assertSame(Widgets::find('counter'), Widgets::findOrFail('counter'));

        Widgets::unregister(CounterWidget::class);
        Widgets::unregister('image');

        $this->assertFalse(Widgets::exists('counter'));
        $this->assertNull(Widgets::find('image'));
    }

    #[Test]
    public function it_refuses_classes_that_arent_widgets(): void
    {
        $this->expectException(WidgetsException::class);

        Widgets::register(stdClass::class);
    }

    #[Test]
    public function it_throws_for_unknown_widgets(): void
    {
        $this->expectException(WidgetsException::class);
        $this->expectExceptionMessage('[nope]');

        Widgets::findOrFail('nope');
    }

    #[Test]
    public function it_describes_a_widget_type(): void
    {
        Widgets::register(CounterWidget::class);

        $widget = Widgets::find('counter');

        $this->assertEquals([
            'key' => 'counter',
            'name' => 'Counter',
            'description' => null,
            'icon' => null,
            'component' => 'Counter',
        ], $widget->toArray());
        $this->assertEquals(['label'], $widget->translatableSettings());
        $this->assertEquals(['count' => 3], $widget->defaults());
        $this->assertEquals('test-widgets.counter', $widget->view());
        $this->assertEquals('widgets::widgets.text', Widgets::find('text')->view());
    }

    #[Test]
    public function it_defines_locations(): void
    {
        Widgets::location('footer', fn () => 'Footer')->order(10);
        Widgets::location('sidebar', 'Sidebar')
            ->description('Next to posts')
            ->allow([TextWidget::class, 'link'])
            ->max(2)
            ->permission('edit_sidebar_widgets');

        $this->assertEquals(['sidebar', 'footer'], array_keys(Widgets::locations()));

        $sidebar = Widgets::findLocation('sidebar');

        $this->assertSame($sidebar, Widgets::location('sidebar'));
        $this->assertEquals('Sidebar', $sidebar->getLabel());
        $this->assertEquals('Next to posts', $sidebar->getDescription());
        $this->assertTrue($sidebar->allows('text'));
        $this->assertTrue($sidebar->allows(Widgets::find('link')));
        $this->assertFalse($sidebar->allows('image'));
        $this->assertEquals(['text', 'link'], array_keys(Widgets::available('sidebar')));
        $this->assertEquals('edit_sidebar_widgets', $sidebar->getPermission());
        $this->assertEquals(['key' => 'sidebar', 'label' => 'Sidebar', 'description' => 'Next to posts', 'max' => 2], $sidebar->toArray());

        Widgets::location('footer')->deny(['image']);

        $this->assertEquals(['text', 'link'], array_keys(Widgets::available('footer')));

        Widgets::removeLocation('footer');

        $this->assertFalse(Widgets::hasLocation('footer'));
    }

    #[Test]
    public function it_names_a_location_from_its_key_when_it_has_no_label(): void
    {
        $this->assertEquals('Footer Column', Widgets::location('footer_column')->getLabel());
    }

    protected function configLocations($app): void
    {
        $app['config']->set('widgets.locations', ['sidebar' => 'Sidebar', 'footer']);
    }

    #[Test]
    #[DefineEnvironment('configLocations')]
    public function it_defines_locations_from_config(): void
    {
        $this->assertEquals('Sidebar', Widgets::findLocation('sidebar')->getLabel());
        $this->assertEquals('Footer', Widgets::findLocation('footer')->getLabel());
    }

    #[Test]
    public function it_throws_for_unknown_locations(): void
    {
        $this->expectException(WidgetsException::class);

        Widgets::available('nowhere');
    }
}
