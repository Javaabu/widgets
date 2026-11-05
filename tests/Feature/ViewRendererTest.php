<?php

namespace Javaabu\Widgets\Tests\Feature;

use Inertia\Inertia;
use Javaabu\Widgets\Tests\TestCase;
use Javaabu\Widgets\Views\ViewRenderer;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ViewRendererTest extends TestCase
{
    #[Test]
    public function it_renders_blade_views_by_default(): void
    {
        $views = new ViewRenderer;

        $this->assertEquals('blade', $views->stack());
        $this->assertFalse($views->isInertia());
        $this->assertEquals('widgets::Widgets/Edit', $views->inertiaPage('widgets.edit'));
    }

    #[Test]
    public function it_explains_that_inertia_is_needed(): void
    {
        if (class_exists(Inertia::class)) {
            $this->markTestSkipped('Runs without inertiajs/inertia-laravel (the without-optional CI job).');
        }

        $this->app['config']->set('widgets.views', 'inertia');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('inertiajs/inertia-laravel');

        (new ViewRenderer)->render('widgets.index');
    }
}
