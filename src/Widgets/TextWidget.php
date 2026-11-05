<?php

namespace Javaabu\Widgets\Widgets;

use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;

/**
 * A title and some text. Line breaks in the text are kept.
 */
class TextWidget extends Widget
{
    public static function key(): string
    {
        return 'text';
    }

    public function name(): string
    {
        return __('Text');
    }

    public function description(): ?string
    {
        return __('A title and some text.');
    }

    public function icon(): ?string
    {
        return 'type';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            Textarea::make('text')->label(__('Text'))->required()->maxLength(5000)->translatable(),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return filled($widget->setting('text')) || filled($widget->setting('title'));
    }
}
