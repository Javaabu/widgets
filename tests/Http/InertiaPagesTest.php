<?php

namespace Javaabu\Widgets\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Widgets\Tests\TestCase;
use Javaabu\Widgets\Views\ViewRenderer;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Inertia pages' props. With WIDGETS_UPDATE_FIXTURES=1 it writes them to
 * tests/js/fixtures, which the React page tests render.
 */
#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithWidgets;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('widgets.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpWidgets();
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function writeFixture(string $name, array $props): void
    {
        if (getenv('WIDGETS_UPDATE_FIXTURES')) {
            file_put_contents(
                __DIR__ . "/../js/fixtures/{$name}.json",
                json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }
    }

    #[Test]
    public function it_renders_the_index_page(): void
    {
        $first = $this->add('text', ['title' => 'މަރުޙަބާ', 'text' => 'Hi'], order: 0);
        $this->add('link', ['label' => 'About', 'url' => '/about'], order: 1);
        $this->add('text', ['text' => 'Footer'], 'footer');

        $response = $this->actingAs($this->user(['edit_widgets', 'view_widgets']))
            ->get('/widgets')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('widgets::Widgets/Index')
                ->where('locations.0.key', 'sidebar')
                ->where('locations.0.label', 'Sidebar')
                ->where('locations.0.can_manage', true)
                ->where('locations.0.widgets.0.id', $first->id)
                ->where('locations.0.widgets.0.title', 'މަރުޙަބާ')
                ->where('locations.0.order_url', 'http://localhost/widgets/sidebar/order')
                ->where('locations.1.key', 'footer')
                ->where('locations.1.can_manage', false)
                ->where('locations.1.available', [])
                ->where('strings.title', 'Widgets')
                ->etc());

        $this->writeFixture('index-props', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_edit_page_with_the_stencil_schema(): void
    {
        $widget = $this->add('link', ['title' => 'ގުޅުއްވާ', 'label' => 'Contact', 'url' => '/contact']);

        $response = $this->actingAs($this->user(['edit_widgets', 'edit_footer_widgets']))
            ->get("/widgets/sidebar/{$widget->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('widgets::Widgets/Edit')
                ->where('widget.key', 'link')
                ->where('location.key', 'sidebar')
                ->where('instance.id', $widget->id)
                ->where('form.method', 'PATCH')
                ->where('form.state.title', 'ގުޅުއްވާ')
                ->where('form.state._location', 'sidebar')
                ->where('form.components.0.name', '_location')
                ->where('index_url', 'http://localhost/widgets')
                ->etc());

        $this->writeFixture('edit-props', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_create_page(): void
    {
        $this->actingAs($this->user())
            ->get('/widgets/sidebar/create?type=text')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('widgets::Widgets/Edit')
                ->where('instance', null)
                ->where('form.method', 'POST')
                ->where('form.action', 'http://localhost/widgets/sidebar?type=text')
                ->where('form.components.0.name', 'title')
                ->etc());
    }

    #[Test]
    public function it_names_the_inertia_pages(): void
    {
        $views = new ViewRenderer;

        $this->assertTrue($views->isInertia());
        $this->assertEquals('widgets::Widgets/Index', $views->inertiaPage('widgets.index'));
    }
}
