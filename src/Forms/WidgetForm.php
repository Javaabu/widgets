<?php

namespace Javaabu\Widgets\Forms;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Javaabu\Stencil\Contracts\ResolvesTranslatableFields;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Form;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Support\Translations;
use Javaabu\Widgets\Widget;
use Javaabu\Widgets\WidgetLocation;

/**
 * The stencil form of a widget's settings: the widget's settings() and, when
 * editing, a select to move it to another location.
 *
 * ```php
 * $form = WidgetForm::for('text', 'sidebar')->action(route('widgets.store', 'sidebar'));
 * $form = WidgetForm::for($widget)->action(route('widgets.update', ['sidebar', $widget]));
 *
 * $widget = WidgetForm::for('text', 'sidebar')->saveWidget($request);
 * ```
 *
 * Settings declared ->translatable() get one input per locale when
 * javaabu/translatable is installed, and are stored as locale => value.
 */
class WidgetForm extends Form implements ResolvesTranslatableFields
{
    /** The name of the location select */
    public const string LOCATION = '_location';

    /**
     * The form for a new widget of a type in a location, or for an existing widget
     */
    public static function for(WidgetInstance|Widget|string $widget, WidgetLocation|string|null $location = null, ?string $guard = null): static
    {
        $instance = $widget instanceof WidgetInstance ? $widget : null;
        $type = match (true) {
            $widget instanceof WidgetInstance => $widget->type,
            $widget instanceof Widget => $widget::key(),
            default => $widget,
        };

        $location = $location instanceof WidgetLocation ? $location->getKey() : ($location ?? $instance?->location);

        $form = static::make('widget-' . $type)->with([
            'type' => $type,
            'location' => $location,
            'widget' => $instance?->getKey(),
            'guard' => $guard,
        ]);

        return $form->model($form->initialRecord($instance));
    }

    protected function setUp(): void
    {
        $this->submit(__('Save widget'));
    }

    protected function definition(): array
    {
        return [
            $this->locationSelect(),
            ...$this->widget()->settings(),
        ];
    }

    public function widget(): Widget
    {
        return Widgets::findOrFail((string) $this->context('type'));
    }

    public function location(): WidgetLocation
    {
        $key = (string) $this->context('location');

        return Widgets::findLocation($key) ?? WidgetLocation::make($key);
    }

    public function instance(): ?WidgetInstance
    {
        $id = $this->context('widget');

        if ($id === null) {
            return null;
        }

        return Widgets::model()::query()->find($id);
    }

    public function guard(): ?string
    {
        return $this->context('guard');
    }

    public function isEditing(): bool
    {
        return $this->context('widget') !== null;
    }

    /**
     * Live fields re-evaluate only for users who may change the location
     */
    public function authorize(): bool
    {
        $user = Auth::guard($this->guard())->user();

        return $user !== null && Gate::forUser($user)->allows('manage', $this->location());
    }

    /**
     * Settings declared translatable get one input per locale
     */
    public function isTranslatableField(string $state_path): bool
    {
        return in_array($state_path, $this->widget()->translatableSettings(), true);
    }

    /**
     * The locations a widget can be moved to: where its type is allowed and
     * the user may manage widgets. Null when there's nowhere else to go.
     */
    protected function locationSelect(): ?Select
    {
        if (! $this->isEditing()) {
            return null;
        }

        $options = $this->locationOptions();

        if (count($options) < 2) {
            return null;
        }

        return Select::make(self::LOCATION)
            ->label(__('Location'))
            ->options($options)
            ->required()
            ->default($this->location()->getKey());
    }

    /**
     * @return array<string, string>
     */
    public function locationOptions(): array
    {
        $user = Auth::guard($this->guard())->user();
        $type = (string) $this->context('type');
        $current = $this->location()->getKey();

        $options = [];

        foreach (Widgets::locations() as $key => $location) {
            if (! $location->allows($type)) {
                continue;
            }

            if ($key !== $current && ($user === null || ! Gate::forUser($user)->allows('manage', $location))) {
                continue;
            }

            $options[$key] = $location->getLabel();
        }

        return $options;
    }

    /**
     * The values the form starts with: the settings (defaults for a new
     * widget) and each locale of a translatable setting ({key}_{locale})
     *
     * @return array<string, mixed>
     */
    public function initialRecord(?WidgetInstance $instance = null): array
    {
        $widget = $this->widget();
        $stored = $instance ? (array) ($instance->settings ?? []) : $widget->defaults();
        $translatable = $widget->translatableSettings();

        $values = $stored;

        foreach ($translatable as $key) {
            $value = data_get($stored, $key);
            $translations = Translations::isLocaleMap($value)
                ? $value
                : ($value === null ? [] : [Translations::defaultLocale() => $value]);

            data_set($values, $key, $translations === [] ? null : Translations::pick($translations));

            foreach (Translations::locales() as $locale) {
                $values["{$key}_{$locale}"] = $translations[$locale] ?? null;
            }
        }

        $values[self::LOCATION] = $instance?->location ?? $this->context('location');

        return $values;
    }

    /**
     * Validate the input and save the widget: a new one at the end of its
     * location, or the existing one (moved when the location changed)
     *
     * @param  Request|array<string, mixed>|null  $input
     */
    public function saveWidget(Request|array|null $input = null): WidgetInstance
    {
        $data = $this->validate($input);

        $instance = $this->instance();
        $model = Widgets::model();

        $widget = $instance ?? new $model([
            'type' => $this->context('type'),
            'location' => $this->location()->getKey(),
        ]);

        $widget->settings = $this->settingsFrom($data, (array) ($instance?->settings ?? []));

        $location = Arr::get($data, self::LOCATION);

        if ($instance === null || (is_string($location) && $location !== $widget->location)) {
            $widget->location = is_string($location) && $location !== '' ? $location : $widget->location;
            $widget->order = $this->nextOrder($widget->location);
        }

        $widget->save();

        return $widget;
    }

    /**
     * The settings to store from validated data. Translatable settings keep
     * the locales that weren't submitted.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function settingsFrom(array $data, array $stored = []): array
    {
        $widget = $this->widget();
        $translatable = $widget->translatableSettings();

        $settings = [];

        foreach ($widget->fields() as $path => $field) {
            if (! $field->isDehydrated()) {
                continue;
            }

            if (in_array($path, $translatable, true) && Translations::enabled()) {
                $previous = data_get($stored, $path);
                $translations = Translations::isLocaleMap($previous) ? $previous : [];

                foreach (Translations::locales() as $locale) {
                    if (array_key_exists("{$path}_{$locale}", $data)) {
                        $translations[$locale] = $data["{$path}_{$locale}"];
                    }
                }

                data_set($settings, $path, $translations);

                continue;
            }

            if (Arr::has($data, $path)) {
                data_set($settings, $path, Arr::get($data, $path));
            }
        }

        return $settings;
    }

    protected function nextOrder(string $location): int
    {
        return (int) Widgets::model()::query()->inLocation($location)->max('order') + 1;
    }
}
