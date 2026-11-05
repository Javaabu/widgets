<?php

namespace Javaabu\Widgets\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\WidgetLocation;
use Javaabu\Widgets\WidgetsManager;

/**
 * The widgets of a location, each in its wrapper:
 *
 * ```blade
 * <x-widgets::location name="sidebar" class="space-y-6" />
 * ```
 *
 * Renders nothing when the location has no widgets, unless `always` is set.
 */
class Location extends Component
{
    public WidgetLocation $location;

    /** @var Collection<int, WidgetInstance> */
    public Collection $widgets;

    public function __construct(
        public string $name,
        public bool $always = false,
    ) {
        $manager = app(WidgetsManager::class);

        $this->location = $manager->findLocation($name) ?? WidgetLocation::make($name);
        $this->widgets = $manager->visible($this->location);
    }

    public function render(): View
    {
        return view('widgets::components.location', [
            'renderer' => app(WidgetsManager::class),
        ]);
    }
}
