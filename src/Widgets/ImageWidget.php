<?php

namespace Javaabu\Widgets\Widgets;

use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Rules\SafeUrl;
use Javaabu\Widgets\Support\Url;
use Javaabu\Widgets\Widget;

/**
 * An image from a URL, with alternative text, a caption and an optional link
 */
class ImageWidget extends Widget
{
    public static function key(): string
    {
        return 'image';
    }

    public function name(): string
    {
        return __('Image');
    }

    public function description(): ?string
    {
        return __('An image with a caption and an optional link.');
    }

    public function icon(): ?string
    {
        return 'image';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            TextInput::make('src')->label(__('Image URL'))->required()->maxLength(2048)->rules([new SafeUrl]),
            TextInput::make('alt')->label(__('Alternative text'))->help(__('Describes the image for people who can\'t see it.'))->maxLength(255)->translatable(),
            TextInput::make('caption')->label(__('Caption'))->maxLength(255)->translatable(),
            TextInput::make('link')->label(__('Link'))->maxLength(2048)->rules([new SafeUrl]),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return Url::isSafe($widget->setting('src'));
    }

    public function data(WidgetInstance $widget): array
    {
        return [
            'src' => Url::safe($widget->setting('src')),
            'link' => Url::safe($widget->setting('link')),
        ];
    }
}
