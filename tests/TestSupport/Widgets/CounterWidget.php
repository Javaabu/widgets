<?php

namespace Javaabu\Widgets\Tests\TestSupport\Widgets;

use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;

class CounterWidget extends Widget
{
    public static function key(): string
    {
        return 'counter';
    }

    public function name(): string
    {
        return __('Counter');
    }

    public function view(): string
    {
        return 'test-widgets.counter';
    }

    public function settings(): array
    {
        return [
            TextInput::make('label')->label(__('Label'))->required()->translatable(),
            TextInput::make('count')->label(__('Count'))->integer()->default(3),
        ];
    }

    public function data(WidgetInstance $widget): array
    {
        return ['doubled' => (int) $widget->setting('count') * 2];
    }
}
