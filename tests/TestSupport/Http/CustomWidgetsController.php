<?php

namespace Javaabu\Widgets\Tests\TestSupport\Http;

use Illuminate\Http\Request;
use Javaabu\Widgets\Http\Controllers\WidgetsController;
use Javaabu\Widgets\Models\WidgetInstance;

class CustomWidgetsController extends WidgetsController
{
    /** @var array<int, int> */
    public static array $saved = [];

    protected function saved(Request $request, WidgetInstance $widget): void
    {
        static::$saved[] = $widget->getKey();
    }
}
