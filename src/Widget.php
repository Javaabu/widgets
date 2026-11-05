<?php

namespace Javaabu\Widgets;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Form;
use Javaabu\Widgets\Models\WidgetInstance;

/**
 * A widget type: what it's called, the settings an admin fills in (stencil
 * components) and how it renders (a Blade view and a React component).
 *
 * ```php
 * class RecentPostsWidget extends Widget
 * {
 *     public static function key(): string { return 'recent-posts'; }
 *
 *     public function name(): string { return __('Recent posts'); }
 *
 *     public function settings(): array
 *     {
 *         return [
 *             TextInput::make('title')->label(__('Title'))->translatable(),
 *             TextInput::make('count')->label(__('Number of posts'))->integer()->default(5),
 *         ];
 *     }
 *
 *     public function data(WidgetInstance $widget): array
 *     {
 *         return ['posts' => Post::latest()->limit($widget->setting('count'))->get(['id', 'title'])];
 *     }
 * }
 * ```
 */
abstract class Widget
{
    /** @var array<string, Field>|null */
    private ?array $resolved_fields = null;

    /**
     * The widget's unique key, stored with each instance: `recent-posts`
     */
    abstract public static function key(): string;

    /**
     * The name admins see when they add the widget
     */
    abstract public function name(): string;

    /**
     * What the widget shows, for the list of widgets admins can add
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * A Lucide icon name for the admin pages
     */
    public function icon(): ?string
    {
        return null;
    }

    /**
     * The settings form: stencil components. Mark text that differs per
     * language with ->translatable().
     *
     * @return array<int, Component|null>
     */
    public function settings(): array
    {
        return [];
    }

    /**
     * Extra data the view and the React component get, computed when the
     * widget renders (a query, a computed URL). Settings are passed already.
     *
     * @return array<string, mixed>
     */
    public function data(WidgetInstance $widget): array
    {
        return [];
    }

    /**
     * The Blade view the widget renders with. It gets `$widget` (the instance),
     * `$settings` (the settings in the current locale) and everything from data().
     */
    public function view(): string
    {
        return 'widgets::widgets.' . static::key();
    }

    /**
     * The name of the React component the widget renders with, looked up in
     * the components given to <WidgetLocation> (or registerWidgets())
     */
    public function component(): string
    {
        return Str::studly(static::key());
    }

    /**
     * A label for one instance in the admin list: its title setting, if any
     */
    public function title(WidgetInstance $widget): ?string
    {
        $title = $widget->setting('title');

        return is_string($title) && $title !== '' ? $title : null;
    }

    /**
     * Whether the widget renders for an instance. Return false to skip it,
     * such as when a required setting is empty.
     */
    public function shouldRender(WidgetInstance $widget): bool
    {
        return true;
    }

    /**
     * The view the widget renders
     */
    public function render(WidgetInstance $widget): View
    {
        return view($this->view(), $this->viewData($widget));
    }

    /**
     * What the view and the React component get
     *
     * @return array<string, mixed>
     */
    public function viewData(WidgetInstance $widget): array
    {
        return [
            ...$this->data($widget),
            'widget' => $widget,
            'settings' => $widget->localizedSettings(),
        ];
    }

    /**
     * The settings form's fields, flattened, keyed by state path
     *
     * @return array<string, Field>
     */
    public function fields(): array
    {
        return $this->resolved_fields ??= Form::make('widget-' . static::key())->schema($this->settings())->getFields();
    }

    /**
     * The keys of the settings declared ->translatable()
     *
     * @return array<int, string>
     */
    public function translatableSettings(): array
    {
        $keys = [];

        foreach ($this->fields() as $path => $field) {
            if ($field->isDeclaredTranslatable()) {
                $keys[] = $path;
            }
        }

        return $keys;
    }

    /**
     * The defaults of the settings, from the fields' default()
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->fields() as $path => $field) {
            $default = $field->getDefaultState();

            if ($default !== null) {
                data_set($defaults, $path, $default);
            }
        }

        return $defaults;
    }

    /**
     * The widget type for the admin pages and React
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => static::key(),
            'name' => $this->name(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'component' => $this->component(),
        ];
    }
}
