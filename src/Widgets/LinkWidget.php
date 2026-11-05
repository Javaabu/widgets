<?php

namespace Javaabu\Widgets\Widgets;

use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Rules\SafeUrl;
use Javaabu\Widgets\Support\Url;
use Javaabu\Widgets\Widget;

/**
 * A call to action: a title, a short description and a link styled as a button
 */
class LinkWidget extends Widget
{
    public static function key(): string
    {
        return 'link';
    }

    public function name(): string
    {
        return __('Link');
    }

    public function description(): ?string
    {
        return __('A short description and a button that links somewhere.');
    }

    public function icon(): ?string
    {
        return 'link';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            Textarea::make('description')->label(__('Description'))->maxLength(1000)->translatable(),
            TextInput::make('label')->label(__('Button text'))->required()->maxLength(255)->translatable(),
            TextInput::make('url')->label(__('Link'))->required()->maxLength(2048)->rules([new SafeUrl]),
            Checkbox::make('new_tab')->label(__('Open in a new tab')),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return Url::isSafe($widget->setting('url')) && filled($widget->setting('label'));
    }

    public function data(WidgetInstance $widget): array
    {
        $url = Url::safe($widget->setting('url'));

        return [
            'url' => $url,
            'external' => $url !== null && Url::isExternal($url),
        ];
    }
}
