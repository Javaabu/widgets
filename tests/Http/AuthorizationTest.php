<?php

namespace Javaabu\Widgets\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Javaabu\Permissions\Permissions;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Tests\TestCase;
use Javaabu\Widgets\Tests\TestSupport\Http\CustomWidgetsController;
use Javaabu\Widgets\Tests\TestSupport\Policies\DenyAllPolicy;
use Javaabu\Widgets\WidgetLocation;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends TestCase
{
    use InteractsWithWidgets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpWidgets();
    }

    #[Test]
    public function it_registers_the_permissions_and_the_permissions_of_locations(): void
    {
        $this->assertTrue(Permissions::has('view_widgets'));
        $this->assertTrue(Permissions::has('edit_widgets'));
        $this->assertTrue(Permissions::has('edit_footer_widgets'));
        $this->assertEquals('Widgets', Permissions::label('widgets'));
        $this->assertDatabaseHas('permissions', ['name' => 'edit_widgets', 'guard_name' => 'web_admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'edit_footer_widgets', 'guard_name' => 'web']);
    }

    #[Test]
    public function it_rejects_guests(): void
    {
        $widget = $this->add('text', ['text' => 'Hi']);

        $this->getJson('/widgets')->assertUnauthorized();
        $this->getJson('/widgets/sidebar/create?type=text')->assertUnauthorized();
        $this->postJson('/widgets/sidebar?type=text', ['text' => 'x'])->assertUnauthorized();
        $this->patchJson("/widgets/sidebar/{$widget->id}", ['text' => 'x'])->assertUnauthorized();
        $this->deleteJson("/widgets/sidebar/{$widget->id}")->assertUnauthorized();
        $this->putJson('/widgets/sidebar/order', ['widgets' => [$widget->id]])->assertUnauthorized();
        $this->getJson('/admin/widgets')->assertUnauthorized();
    }

    #[Test]
    public function it_forbids_users_without_a_widgets_permission(): void
    {
        $user = $this->user([]);
        $widget = $this->add('text', ['text' => 'Hi']);

        $this->actingAs($user)->get('/widgets')->assertForbidden();
        $this->actingAs($user)->get('/widgets/sidebar/create?type=text')->assertForbidden();
        $this->actingAs($user)->post('/widgets/sidebar?type=text', ['text' => 'x'])->assertForbidden();
        $this->actingAs($user)->get("/widgets/sidebar/{$widget->id}/edit")->assertForbidden();
        $this->actingAs($user)->patch("/widgets/sidebar/{$widget->id}", ['text' => 'x'])->assertForbidden();
        $this->actingAs($user)->delete("/widgets/sidebar/{$widget->id}")->assertForbidden();
        $this->actingAs($user)->put('/widgets/sidebar/order', ['widgets' => [$widget->id]])->assertForbidden();

        $this->assertModelExists($widget);
    }

    #[Test]
    public function it_lets_viewers_see_but_not_change_widgets(): void
    {
        $user = $this->user(['view_widgets']);
        $widget = $this->add('text', ['text' => 'Hi']);

        $response = $this->actingAs($user)->getJson('/widgets')->assertOk();

        $this->assertEquals(['sidebar', 'footer'], array_column($response->json('data'), 'key'));
        $this->assertFalse($response->json('data.0.can_manage'));
        $this->assertEquals([], $response->json('data.0.available'));

        $this->actingAs($user)->get('/widgets')->assertOk()->assertDontSee('data-widgets-sortable', false)->assertDontSee('data-widgets-handle', false);
        $this->actingAs($user)->get('/widgets/sidebar/create?type=text')->assertForbidden();
        $this->actingAs($user)->patch("/widgets/sidebar/{$widget->id}", ['text' => 'x'])->assertForbidden();
    }

    #[Test]
    public function it_uses_the_permission_of_a_location(): void
    {
        $user = $this->user(['edit_footer_widgets']);
        $footer = $this->add('text', ['text' => 'Footer'], 'footer');
        $sidebar = $this->add('text', ['text' => 'Sidebar']);

        $response = $this->actingAs($user)->getJson('/widgets')->assertOk();

        $this->assertEquals(['footer'], array_column($response->json('data'), 'key'));

        $this->actingAs($user)->patch("/widgets/footer/{$footer->id}", ['text' => 'ފޫޓަރ'])->assertRedirect();
        $this->actingAs($user)->patch("/widgets/sidebar/{$sidebar->id}", ['text' => 'x'])->assertForbidden();

        // edit_widgets doesn't cover a location with its own permission
        $editor = $this->user(['edit_widgets']);

        $this->actingAs($editor)->patch("/widgets/footer/{$footer->id}", ['text' => 'x'])->assertForbidden();
        $this->assertEquals('ފޫޓަރ', $footer->fresh()->settings['text']);
    }

    #[Test]
    public function it_works_for_another_guard_and_user_model(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'web_admin')
            ->post('/admin/widgets/sidebar?type=text', ['text' => 'ޓެކްސްޓް'])
            ->assertRedirect('/admin/widgets');

        $widget = WidgetInstance::first();

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/widgets')
            ->assertOk()
            ->assertSee('/admin/widgets/sidebar/' . $widget->id . '/edit', false);

        $this->actingAs($admin, 'web_admin')
            ->get("/admin/widgets/sidebar/{$widget->id}/edit")
            ->assertOk()
            ->assertSee('action="http://localhost/admin/widgets/sidebar/' . $widget->id . '"', false);
    }

    #[Test]
    public function it_checks_the_permissions_of_the_routes_guard(): void
    {
        $user = $this->user(['view_widgets']);

        $this->actingAs($user)->getJson('/widgets')->assertOk();
        $this->actingAs($this->admin([]), 'web_admin')->getJson('/admin/widgets')->assertForbidden();
    }

    #[Test]
    public function apps_can_override_the_policy_with_gate_policy(): void
    {
        Gate::policy(WidgetLocation::class, DenyAllPolicy::class);

        $this->actingAs($this->user())->get('/widgets')->assertForbidden();
    }

    #[Test]
    public function apps_can_override_the_controller(): void
    {
        $this->app['router']->middleware('web')->group(function ($router) {
            $router->widgets(['prefix' => 'custom/widgets', 'name' => 'custom.widgets', 'controller' => CustomWidgetsController::class]);
        });

        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->actingAs($this->user())
            ->post('/custom/widgets/sidebar?type=text', ['text' => 'x'])
            ->assertRedirect('/custom/widgets');

        $this->assertEquals([WidgetInstance::first()->id], CustomWidgetsController::$saved);
    }

    #[Test]
    public function it_can_skip_registering_permissions(): void
    {
        $this->assertNotNull(Permissions::group('widgets'));
        $this->assertEquals(config('widgets.policy'), Permissions::policies()[WidgetLocation::class]);
        $this->assertArrayHasKey(Widgets::model(), Permissions::policies());
    }
}
