<?php

namespace Javaabu\Widgets;

use Closure;

/**
 * A place in the theme where widgets go: a sidebar, a footer column.
 *
 * ```php
 * Widgets::location('sidebar', fn () => __('Sidebar'))
 *     ->description(fn () => __('Shown next to posts'))
 *     ->max(6);
 *
 * Widgets::location('footer', fn () => __('Footer'))
 *     ->allow([TextWidget::class, LinkWidget::class])
 *     ->permission('edit_footer_widgets');
 * ```
 */
class WidgetLocation
{
    protected string|Closure|null $label = null;

    protected string|Closure|null $description = null;

    /** @var array<int, string>|null widget keys; null allows every widget */
    protected ?array $allowed = null;

    /** @var array<int, string> widget keys */
    protected array $denied = [];

    protected ?int $max = null;

    protected ?string $permission = null;

    protected ?string $wrapper = null;

    protected int $order = 0;

    public function __construct(
        protected string $key,
    ) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * The name admins see. Pass a closure so it's translated when shown.
     */
    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        $label = $this->label instanceof Closure ? ($this->label)() : $this->label;

        return $label === null || $label === '' ? str($this->key)->headline()->toString() : __($label);
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        $description = $this->description instanceof Closure ? ($this->description)() : $this->description;

        return $description === null || $description === '' ? null : __($description);
    }

    /**
     * Only these widgets can be added here
     *
     * @param  array<int, class-string<Widget>|string>  $widgets  classes or keys
     */
    public function allow(array $widgets): static
    {
        $this->allowed = array_map(fn (string $widget) => static::widgetKey($widget), $widgets);

        return $this;
    }

    /**
     * These widgets can't be added here
     *
     * @param  array<int, class-string<Widget>|string>  $widgets  classes or keys
     */
    public function deny(array $widgets): static
    {
        $this->denied = array_map(fn (string $widget) => static::widgetKey($widget), $widgets);

        return $this;
    }

    public function allows(Widget|string $widget): bool
    {
        $key = $widget instanceof Widget ? $widget::key() : static::widgetKey($widget);

        if (in_array($key, $this->denied, true)) {
            return false;
        }

        return $this->allowed === null || in_array($key, $this->allowed, true);
    }

    /**
     * The most widgets the location holds; null for no limit
     */
    public function max(?int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    /**
     * The permission needed to change this location's widgets, instead of
     * edit_widgets. It's registered with javaabu/permissions for you.
     */
    public function permission(?string $permission): static
    {
        $this->permission = $permission;

        if ($permission !== null && app()->isBooted()) {
            app(WidgetsManager::class)->registerLocationPermissions();
        }

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * The Blade view each widget is wrapped in here, instead of
     * config('widgets.wrapper'). It gets `$widget`, `$location` and `$slot`.
     */
    public function wrapper(?string $view): static
    {
        $this->wrapper = $view;

        return $this;
    }

    public function getWrapper(): string
    {
        return $this->wrapper ?? config('widgets.wrapper', 'widgets::wrapper');
    }

    /**
     * Where the location is listed on the admin page, lowest first
     */
    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->getKey(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'max' => $this->getMax(),
        ];
    }

    protected static function widgetKey(string $widget): string
    {
        if (is_subclass_of($widget, Widget::class)) {
            return $widget::key();
        }

        return $widget;
    }
}
