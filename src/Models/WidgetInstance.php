<?php

namespace Javaabu\Widgets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Support\Translations;
use Javaabu\Widgets\Widget;
use Javaabu\Widgets\WidgetLocation;

/**
 * A widget added to a location, with its settings.
 *
 * @property int $id
 * @property string $location
 * @property string $type
 * @property array<string, mixed>|null $settings
 * @property int $order
 */
class WidgetInstance extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'order' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('widgets.table', 'widget_instances');
    }

    protected static function booted(): void
    {
        static::saved(fn (self $widget) => Widgets::flush());
        static::deleted(fn (self $widget) => Widgets::flush());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeInLocation(Builder $query, WidgetLocation|string $location): void
    {
        $query->where('location', $location instanceof WidgetLocation ? $location->getKey() : $location);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order')->orderBy('id');
    }

    /**
     * The widget type, or null when it's no longer registered
     */
    public function definition(): ?Widget
    {
        return Widgets::find($this->type);
    }

    /**
     * The location, or null when it's no longer registered
     */
    public function getLocation(): ?WidgetLocation
    {
        return Widgets::findLocation($this->location);
    }

    /**
     * A setting in the current locale (translatable settings fall back to the
     * default locale), or the field's default
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = $this->localizedSettings();

        return data_get($settings, $key, $default);
    }

    /**
     * The settings with each translatable one in the current locale, and the
     * defaults of settings that were never saved
     *
     * @return array<string, mixed>
     */
    public function localizedSettings(?string $locale = null): array
    {
        $definition = $this->definition();
        $settings = (array) ($this->settings ?? []);

        if (! $definition) {
            return $settings;
        }

        $settings = array_replace_recursive($definition->defaults(), $settings);

        foreach ($definition->translatableSettings() as $key) {
            $value = data_get($settings, $key);

            if (Translations::isLocaleMap($value)) {
                data_set($settings, $key, Translations::pick($value, $locale));
            }
        }

        return $settings;
    }

    /**
     * Every locale of a translatable setting, locale => value
     *
     * @return array<string, mixed>
     */
    public function translations(string $key): array
    {
        $value = data_get($this->settings ?? [], $key);

        if (Translations::isLocaleMap($value)) {
            return $value;
        }

        return $value === null ? [] : [Translations::defaultLocale() => $value];
    }

    /**
     * A label for the admin list: the widget's title, else its name
     */
    public function displayTitle(): string
    {
        $definition = $this->definition();

        if (! $definition) {
            return $this->type;
        }

        return $definition->title($this) ?? $definition->name();
    }
}
