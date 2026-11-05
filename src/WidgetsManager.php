<?php

namespace Javaabu\Widgets;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Javaabu\Permissions\Permissions;
use Javaabu\Widgets\Exceptions\WidgetsException;
use Javaabu\Widgets\Models\WidgetInstance;

/**
 * The registry of widget types and locations, and the widgets in each
 * location. Use it through the `Widgets` facade.
 */
class WidgetsManager
{
    /** @var array<string, class-string<Widget>> */
    protected array $types = [];

    /** @var array<string, Widget> */
    protected array $resolved = [];

    /** @var array<string, WidgetLocation> */
    protected array $locations = [];

    /** @var array<string, Collection<int, WidgetInstance>> widgets loaded in this request, per location */
    protected array $loaded = [];

    public function __construct(
        protected Container $container,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Widget types
    |--------------------------------------------------------------------------
    */

    /**
     * Register widget types
     *
     * @param  class-string<Widget>|array<int, class-string<Widget>>  $widgets
     */
    public function register(string|array $widgets): static
    {
        foreach ((array) $widgets as $widget) {
            if (! is_subclass_of($widget, Widget::class)) {
                throw WidgetsException::notAWidget($widget);
            }

            $this->types[$widget::key()] = $widget;
            unset($this->resolved[$widget::key()]);
        }

        return $this;
    }

    /**
     * Remove a widget type, such as a built-in one the app doesn't want
     *
     * @param  class-string<Widget>|string  $widget  a class or a key
     */
    public function unregister(string $widget): static
    {
        $key = is_subclass_of($widget, Widget::class) ? $widget::key() : $widget;

        unset($this->types[$key], $this->resolved[$key]);

        return $this;
    }

    /**
     * Every registered widget type, keyed by key
     *
     * @return array<string, Widget>
     */
    public function all(): array
    {
        $widgets = [];

        foreach (array_keys($this->types) as $key) {
            $widgets[$key] = $this->find($key);
        }

        return $widgets;
    }

    public function find(string $key): ?Widget
    {
        if (! isset($this->types[$key])) {
            return null;
        }

        return $this->resolved[$key] ??= $this->container->make($this->types[$key]);
    }

    public function findOrFail(string $key): Widget
    {
        return $this->find($key) ?? throw WidgetsException::unknownWidget($key);
    }

    public function exists(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * The widget types an admin can add to a location
     *
     * @return array<string, Widget>
     */
    public function available(WidgetLocation|string $location): array
    {
        $location = $this->resolveLocation($location);

        return array_filter($this->all(), fn (Widget $widget) => $location->allows($widget));
    }

    /*
    |--------------------------------------------------------------------------
    | Locations
    |--------------------------------------------------------------------------
    */

    /**
     * Define a location, or get one to change it
     */
    public function location(string $key, string|Closure|null $label = null): WidgetLocation
    {
        $location = $this->locations[$key] ??= WidgetLocation::make($key);

        if ($label !== null) {
            $location->label($label);
        }

        return $location;
    }

    /**
     * Every location, in order
     *
     * @return array<string, WidgetLocation>
     */
    public function locations(): array
    {
        $locations = $this->locations;
        $positions = array_flip(array_keys($locations));

        uasort($locations, fn (WidgetLocation $a, WidgetLocation $b) => [$a->getOrder(), $positions[$a->getKey()]] <=> [$b->getOrder(), $positions[$b->getKey()]]);

        return $locations;
    }

    public function findLocation(string $key): ?WidgetLocation
    {
        return $this->locations[$key] ?? null;
    }

    public function hasLocation(string $key): bool
    {
        return isset($this->locations[$key]);
    }

    public function removeLocation(string $key): static
    {
        unset($this->locations[$key]);

        return $this;
    }

    /**
     * Register the permissions of locations that have their own with
     * javaabu/permissions. Runs after the app boots, and again when a
     * location is given a permission later.
     */
    public function registerLocationPermissions(): void
    {
        if (! config('widgets.register_permissions', true) || ! class_exists(Permissions::class)) {
            return;
        }

        $group = Permissions::group('widgets');

        foreach ($this->locations as $location) {
            $permission = $location->getPermission();

            if ($permission === null || Permissions::has($permission)) {
                continue;
            }

            $label = Str::headline($location->getKey());

            $group?->add([$permission => "Edit {$label} widgets"]);
        }
    }

    protected function resolveLocation(WidgetLocation|string $location): WidgetLocation
    {
        if ($location instanceof WidgetLocation) {
            return $location;
        }

        return $this->findLocation($location) ?? throw WidgetsException::unknownLocation($location);
    }

    /*
    |--------------------------------------------------------------------------
    | Widgets in a location
    |--------------------------------------------------------------------------
    */

    /**
     * The model class of widget instances, from config('widgets.model')
     *
     * @return class-string<WidgetInstance>
     */
    public function model(): string
    {
        return config('widgets.model') ?: WidgetInstance::class;
    }

    /**
     * Every widget in a location, in order, including ones whose type is no
     * longer registered
     *
     * @return Collection<int, WidgetInstance>
     */
    public function widgets(WidgetLocation|string $location): Collection
    {
        $key = $location instanceof WidgetLocation ? $location->getKey() : $location;

        return $this->loaded[$key] ??= $this->load($key);
    }

    /**
     * The widgets that render in a location: registered types that want to render
     *
     * @return Collection<int, WidgetInstance>
     */
    public function visible(WidgetLocation|string $location): Collection
    {
        return $this->widgets($location)
            ->filter(fn (WidgetInstance $widget) => $widget->definition()?->shouldRender($widget) ?? false)
            ->values();
    }

    /**
     * Whether a location has any widget to render
     */
    public function has(WidgetLocation|string $location): bool
    {
        return $this->visible($location)->isNotEmpty();
    }

    /**
     * The HTML of a location's widgets, each in its wrapper. Empty when there are none.
     */
    public function render(WidgetLocation|string $location): HtmlString
    {
        $key = $location instanceof WidgetLocation ? $location->getKey() : $location;
        $location = $this->findLocation($key) ?? WidgetLocation::make($key);

        $html = '';

        foreach ($this->visible($location) as $widget) {
            $html .= $this->renderWidget($widget, $location);
        }

        return new HtmlString($html);
    }

    /**
     * One widget in its wrapper
     */
    public function renderWidget(WidgetInstance $widget, ?WidgetLocation $location = null): HtmlString
    {
        $location ??= $widget->getLocation() ?? WidgetLocation::make($widget->location);
        $definition = $widget->definition();

        if (! $definition) {
            return new HtmlString('');
        }

        return new HtmlString(view($location->getWrapper(), [
            'widget' => $widget,
            'definition' => $definition,
            'location' => $location,
            'slot' => new HtmlString(trim($definition->render($widget)->render())),
        ])->render());
    }

    /**
     * A location's widgets for React (<WidgetLocation widgets={...} />)
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(WidgetLocation|string $location): array
    {
        return $this->visible($location)
            ->map(fn (WidgetInstance $widget) => $this->widgetToArray($widget))
            ->all();
    }

    /**
     * Several locations for React, keyed by location: for a shared Inertia prop
     *
     * ```php
     * Inertia::share('widgets', fn () => Widgets::props(['sidebar', 'footer']));
     * ```
     *
     * @param  array<int, string>|null  $locations  null for every location
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function props(?array $locations = null): array
    {
        $locations ??= array_keys($this->locations());

        $props = [];

        foreach ($locations as $location) {
            $props[$location] = $this->toArray($location);
        }

        return $props;
    }

    /**
     * @return array<string, mixed>
     */
    public function widgetToArray(WidgetInstance $widget): array
    {
        $definition = $widget->definition();
        $data = $definition?->viewData($widget) ?? [];

        unset($data['widget'], $data['settings']);

        return [
            'id' => $widget->getKey(),
            'type' => $widget->type,
            'component' => $definition?->component(),
            'location' => $widget->location,
            'settings' => $widget->localizedSettings(),
            'data' => (object) $data,
        ];
    }

    /**
     * Forget the widgets loaded in this request and the cache. Saving or
     * deleting a widget calls it.
     */
    public function flush(): void
    {
        $this->loaded = [];

        if ($this->cacheEnabled()) {
            $this->cache()->forever($this->cacheVersionKey(), Str::random(12));
        }
    }

    /**
     * @return Collection<int, WidgetInstance>
     */
    protected function load(string $location): Collection
    {
        $query = fn () => $this->model()::query()->inLocation($location)->ordered()->get();

        if (! $this->cacheEnabled()) {
            return $query();
        }

        $key = $this->cachePrefix() . '.' . $this->cacheVersion() . '.' . $location;

        return $this->cache()->rememberForever($key, $query);
    }

    protected function cacheEnabled(): bool
    {
        return (bool) config('widgets.cache.enabled', false);
    }

    protected function cache(): CacheRepository
    {
        return Cache::store(config('widgets.cache.store'));
    }

    protected function cachePrefix(): string
    {
        return config('widgets.cache.prefix', 'javaabu_widgets');
    }

    protected function cacheVersionKey(): string
    {
        return $this->cachePrefix() . '.version';
    }

    protected function cacheVersion(): string
    {
        return (string) $this->cache()->rememberForever($this->cacheVersionKey(), fn () => Str::random(12));
    }
}
