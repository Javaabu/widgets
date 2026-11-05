<?php

namespace Javaabu\Widgets\Tests\TestSupport\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Javaabu\Widgets\Tests\TestSupport\Models\Admin;
use Javaabu\Widgets\Tests\TestSupport\Models\User;

class TestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database');

        View::addLocation(__DIR__ . '/../views');

        Relation::morphMap([
            'user' => User::class,
            'admin' => Admin::class,
        ]);
    }
}
