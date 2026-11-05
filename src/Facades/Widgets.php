<?php

namespace Javaabu\Widgets\Facades;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;
use Javaabu\Widgets\WidgetLocation;
use Javaabu\Widgets\WidgetsManager;

/**
 * @method static WidgetsManager register(string|array $widgets)
 * @method static WidgetsManager unregister(string $widget)
 * @method static array<string, Widget> all()
 * @method static Widget|null find(string $key)
 * @method static Widget findOrFail(string $key)
 * @method static bool exists(string $key)
 * @method static array<string, Widget> available(WidgetLocation|string $location)
 * @method static WidgetLocation location(string $key, string|Closure|null $label = null)
 * @method static array<string, WidgetLocation> locations()
 * @method static WidgetLocation|null findLocation(string $key)
 * @method static bool hasLocation(string $key)
 * @method static WidgetsManager removeLocation(string $key)
 * @method static class-string<WidgetInstance> model()
 * @method static Collection<int, WidgetInstance> widgets(WidgetLocation|string $location)
 * @method static Collection<int, WidgetInstance> visible(WidgetLocation|string $location)
 * @method static bool has(WidgetLocation|string $location)
 * @method static HtmlString render(WidgetLocation|string $location)
 * @method static HtmlString renderWidget(WidgetInstance $widget, ?WidgetLocation $location = null)
 * @method static array<int, array<string, mixed>> toArray(WidgetLocation|string $location)
 * @method static array<string, array<int, array<string, mixed>>> props(?array $locations = null)
 * @method static array<string, mixed> widgetToArray(WidgetInstance $widget)
 * @method static void flush()
 *
 * @see WidgetsManager
 */
class Widgets extends Facade
{
    /** Set to false to stop the package from loading its migration, e.g. after publishing it */
    public static bool $runs_migrations = true;

    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    protected static function getFacadeAccessor(): string
    {
        return WidgetsManager::class;
    }
}
