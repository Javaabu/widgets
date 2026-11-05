<?php

namespace Javaabu\Widgets\Tests\Http;

use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WidgetsControllerTest extends TestCase
{
    use InteractsWithWidgets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpWidgets();
    }

    #[Test]
    public function it_lists_the_locations_and_their_widgets(): void
    {
        $widget = $this->add('text', ['title' => 'މަރުޙަބާ', 'text' => 'Hi']);
        $this->add('removed', [], order: 1);

        $this->actingAs($this->user(['edit_widgets', 'edit_footer_widgets']))
            ->get('/widgets')
            ->assertOk()
            ->assertSee('data-widgets-location="sidebar"', false)
            ->assertSee('data-widgets-location="footer"', false)
            ->assertSee('Next to posts')
            ->assertSee('މަރުޙަބާ')
            ->assertSee('data-widget-id="' . $widget->id . '"', false)
            ->assertSee('data-widgets-sortable', false)
            ->assertSee('/widgets/sidebar/' . $widget->id . '/edit', false)
            ->assertSee('This widget type is no longer available.')
            ->assertSee('<option value="counter">Counter</option>', false)
            ->assertSee('aria-describedby="widgets-instructions"', false);

        Manifest::assertAssetRequired('widgets::admin');
    }

    #[Test]
    public function it_lists_locations_as_json(): void
    {
        $widget = $this->add('text', ['text' => 'Hi']);

        $response = $this->actingAs($this->user())->getJson('/widgets')->assertOk();

        $this->assertEquals(['sidebar'], array_column($response->json('data'), 'key'));
        $this->assertEquals($widget->id, $response->json('data.0.widgets.0.id'));
        $this->assertEquals('Text', $response->json('data.0.widgets.0.title'));
        $this->assertEquals(['text', 'image', 'link', 'counter'], array_column($response->json('data.0.available'), 'key'));
        $this->assertTrue($response->json('data.0.can_manage'));
    }

    #[Test]
    public function it_shows_the_form_for_a_new_widget(): void
    {
        $this->actingAs($this->user())
            ->get('/widgets/sidebar/create?type=text')
            ->assertOk()
            ->assertSee('Add Text to Sidebar')
            ->assertSee('action="http://localhost/widgets/sidebar?type=text"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="text"', false)
            ->assertDontSee('name="_location"', false);
    }

    #[Test]
    public function it_refuses_unknown_or_disallowed_widget_types(): void
    {
        $user = $this->user(['edit_widgets', 'edit_footer_widgets']);

        $this->actingAs($user)->get('/widgets/sidebar/create?type=nope')->assertNotFound();
        $this->actingAs($user)->get('/widgets/footer/create?type=image')->assertNotFound();
        $this->actingAs($user)->get('/widgets/nowhere/create?type=text')->assertNotFound();
        $this->actingAs($user)->post('/widgets/footer?type=counter', ['label' => 'x'])->assertNotFound();
    }

    #[Test]
    public function it_adds_a_widget_at_the_end_of_the_location(): void
    {
        $this->add('text', ['text' => 'First'], order: 4);

        $this->actingAs($this->user())
            ->post('/widgets/sidebar?type=text', ['title' => 'ސުރުހީ', 'text' => 'ލިޔުން'])
            ->assertRedirect('/widgets')
            ->assertSessionHas('status', 'Widget added.');

        $widget = WidgetInstance::query()->latest('id')->first();

        $this->assertEquals('sidebar', $widget->location);
        $this->assertEquals('text', $widget->type);
        $this->assertEquals(['title' => 'ސުރުހީ', 'text' => 'ލިޔުން'], $widget->settings);
        $this->assertEquals(5, $widget->order);
    }

    #[Test]
    public function it_validates_the_settings(): void
    {
        $this->actingAs($this->user())
            ->from('/widgets/sidebar/create?type=link')
            ->post('/widgets/sidebar?type=link', ['label' => 'Go', 'url' => 'javascript:alert(1)'])
            ->assertRedirect('/widgets/sidebar/create?type=link')
            ->assertSessionHasErrors('url');

        $this->actingAs($this->user())
            ->postJson('/widgets/sidebar?type=text', ['title' => 'Only a title'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');

        $this->assertEquals(0, WidgetInstance::count());
    }

    #[Test]
    public function it_adds_a_widget_with_json(): void
    {
        $response = $this->actingAs($this->user())
            ->postJson('/widgets/sidebar', ['type' => 'counter', 'label' => 'ގިނަ', 'count' => '7'])
            ->assertCreated()
            ->assertJsonPath('message', 'Widget added.')
            ->assertJsonPath('widget.type', 'counter');

        $this->assertEquals(['label' => 'ގިނަ', 'count' => 7], WidgetInstance::find($response->json('widget.id'))->settings);
    }

    #[Test]
    public function it_refuses_new_widgets_in_a_full_location(): void
    {
        $user = $this->user(['edit_footer_widgets']);
        $this->add('text', ['text' => 'One'], 'footer');
        $this->add('text', ['text' => 'Two'], 'footer');

        $this->actingAs($user)
            ->postJson('/widgets/footer?type=text', ['text' => 'Three'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type' => 'Footer holds only 2 widgets.']);

        $this->actingAs($user)->get('/widgets')->assertSee('This location is full.');
    }

    #[Test]
    public function it_edits_a_widget(): void
    {
        $widget = $this->add('text', ['title' => 'ކުރިން', 'text' => 'Before']);

        $this->actingAs($this->user())
            ->get("/widgets/sidebar/{$widget->id}/edit")
            ->assertOk()
            ->assertSee('Edit Text in Sidebar')
            ->assertSee('value="ކުރިން"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertDontSee('name="_location"', false);

        $this->actingAs($this->user())
            ->patch("/widgets/sidebar/{$widget->id}", ['title' => 'ފަހުން', 'text' => 'After'])
            ->assertRedirect('/widgets')
            ->assertSessionHas('status', 'Widget saved.');

        $this->assertEquals(['title' => 'ފަހުން', 'text' => 'After'], $widget->fresh()->settings);
    }

    #[Test]
    public function it_returns_a_widget_as_json(): void
    {
        $widget = $this->add('text', ['text' => 'Hi']);

        $this->actingAs($this->user())
            ->getJson("/widgets/sidebar/{$widget->id}/edit")
            ->assertOk()
            ->assertJsonPath('widget.id', $widget->id)
            ->assertJsonPath('settings.text', 'Hi');

        $this->actingAs($this->user())
            ->getJson('/widgets/sidebar/create?type=text')
            ->assertOk()
            ->assertJsonPath('widget.key', 'text')
            ->assertJsonPath('form.stencil', 1);
    }

    #[Test]
    public function it_moves_a_widget_to_another_location(): void
    {
        $user = $this->user(['edit_widgets', 'edit_footer_widgets']);
        $this->add('text', ['text' => 'Already there'], 'footer', order: 3);
        $widget = $this->add('text', ['text' => 'Moving']);

        $this->actingAs($user)
            ->get("/widgets/sidebar/{$widget->id}/edit")
            ->assertSee('name="_location"', false);

        $this->actingAs($user)
            ->patch("/widgets/sidebar/{$widget->id}", ['_location' => 'footer', 'text' => 'Moved'])
            ->assertRedirect('/widgets');

        $widget->refresh();

        $this->assertEquals('footer', $widget->location);
        $this->assertEquals(4, $widget->order);
        $this->assertEquals(['title' => null, 'text' => 'Moved'], $widget->settings);
    }

    #[Test]
    public function it_refuses_to_move_a_widget_where_it_cant_go(): void
    {
        $widget = $this->add('counter', ['label' => 'x']);
        $user = $this->user(['edit_widgets', 'edit_footer_widgets']);

        $this->actingAs($user)
            ->patchJson("/widgets/sidebar/{$widget->id}", ['_location' => 'footer', 'label' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('_location');

        $text = $this->add('text', ['text' => 'x']);

        // Without the footer's permission
        $this->actingAs($this->user())
            ->patchJson("/widgets/sidebar/{$text->id}", ['_location' => 'footer', 'text' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('_location');

        $this->add('text', ['text' => '1'], 'footer');
        $this->add('text', ['text' => '2'], 'footer');

        $this->actingAs($user)
            ->patchJson("/widgets/sidebar/{$text->id}", ['_location' => 'footer', 'text' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('_location');

        $this->assertEquals('sidebar', $text->fresh()->location);
    }

    #[Test]
    public function it_only_finds_widgets_in_the_location_given(): void
    {
        $widget = $this->add('text', ['text' => 'Hi'], 'footer');

        $this->actingAs($this->user(['edit_widgets', 'edit_footer_widgets']))
            ->get("/widgets/sidebar/{$widget->id}/edit")
            ->assertNotFound();
    }

    #[Test]
    public function it_doesnt_edit_widgets_whose_type_is_gone(): void
    {
        $widget = $this->add('removed', []);

        $this->actingAs($this->user())->get("/widgets/sidebar/{$widget->id}/edit")->assertNotFound();
        $this->actingAs($this->user())->patch("/widgets/sidebar/{$widget->id}", [])->assertNotFound();
    }

    #[Test]
    public function it_removes_a_widget(): void
    {
        $widget = $this->add('removed', []);

        $this->actingAs($this->user())
            ->delete("/widgets/sidebar/{$widget->id}")
            ->assertRedirect('/widgets')
            ->assertSessionHas('status', 'Widget removed.');

        $this->assertModelMissing($widget);

        $other = $this->add('text', ['text' => 'Hi']);

        $this->actingAs($this->user())
            ->deleteJson("/widgets/sidebar/{$other->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Widget removed.');
    }

    #[Test]
    public function it_orders_the_widgets_of_a_location(): void
    {
        $a = $this->add('text', ['text' => 'A'], order: 0);
        $b = $this->add('text', ['text' => 'B'], order: 1);
        $c = $this->add('text', ['text' => 'C'], order: 2);

        $this->actingAs($this->user())
            ->putJson('/widgets/sidebar/order', ['widgets' => [$c->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('widgets', [$c->id, $a->id, $b->id]);

        $this->assertEquals([$c->id, $a->id, $b->id], WidgetInstance::query()->inLocation('sidebar')->ordered()->pluck('id')->all());

        $this->actingAs($this->user())
            ->put('/widgets/sidebar/order', ['widgets' => [$b->id, $c->id, $a->id]])
            ->assertRedirect('/widgets')
            ->assertSessionHas('status', 'Widget order saved.');

        $this->assertEquals([$b->id, $c->id, $a->id], WidgetInstance::query()->inLocation('sidebar')->ordered()->pluck('id')->all());
    }

    #[Test]
    public function it_refuses_to_order_widgets_from_another_location(): void
    {
        $a = $this->add('text', ['text' => 'A']);
        $other = $this->add('text', ['text' => 'B'], 'footer');

        $this->actingAs($this->user())
            ->putJson('/widgets/sidebar/order', ['widgets' => [$other->id, $a->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('widgets');

        $this->actingAs($this->user())
            ->putJson('/widgets/sidebar/order', ['widgets' => 'nope'])
            ->assertUnprocessable();
    }

    #[Test]
    public function it_shows_the_pages_in_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->actingAs($this->user())
            ->get('/widgets')
            ->assertOk()
            ->assertSee(__('Widgets'))
            ->assertSee('dir="rtl"', false);
    }
}
