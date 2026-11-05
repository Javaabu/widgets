---
title: Locations
sidebar_position: 1
---

A location is a place in your theme where admins can put widgets: a sidebar, a footer column, the top of the home page. Locations are defined in code. Admins can't create them.

# Defining a location

```php
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Widgets\ImageWidget;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;

Widgets::location('sidebar', fn () => __('Sidebar'))
    ->description(fn () => __('Shown next to posts and pages'))
    ->max(6);

Widgets::location('footer', fn () => __('Footer'))
    ->allow([TextWidget::class, LinkWidget::class])
    ->max(3)
    ->permission('edit_footer_widgets')
    ->order(10);

Widgets::location('home_top', fn () => __('Home page'))
    ->deny([ImageWidget::class])
    ->wrapper('theme.widgets.card');
```

| Method | What it does |
| --- | --- |
| `Widgets::location($key, $label)` | Defines the location, or returns it to change it. The label is a string or a closure, translated with `__()` when shown. Without a label, it's the headline of the key. |
| `->description($text)` | Shown under the location on the admin page |
| `->allow([...])` | Only these widgets can be added: classes or keys |
| `->deny([...])` | These widgets can't be added |
| `->max($count)` | The most widgets the location holds. Admins can't add or move more widgets into it. |
| `->permission($name)` | The permission needed to change this location's widgets, instead of `edit_widgets`. It's registered with javaabu/permissions for you. See [Permissions](permissions.md). |
| `->wrapper($view)` | The Blade view each widget is wrapped in, instead of `config('widgets.wrapper')` |
| `->order($position)` | Where the location is listed on the admin page, lowest first, then in the order defined |

Locations can also be listed in `config/widgets.php` when a label is all they need:

```php
'locations' => [
    'sidebar' => 'Sidebar',
    'footer' => 'Footer',
],
```

`Widgets::locations()` returns every location in order. `Widgets::findLocation('sidebar')` returns one or null, and `Widgets::removeLocation('footer')` removes one. Widgets in a location that no longer exists stay in the database, but they don't render or appear on the admin page.

# Rendering a location in Blade

```blade
<aside>
    <x-widgets::location name="sidebar" class="flex flex-col gap-6" />
</aside>
```

It renders:

```html
<div class="widgets widgets-sidebar flex flex-col gap-6" data-widget-location="sidebar">
    <section class="widget widget-text" id="widget-12" data-widget="text">
        <h2 class="widget-title">މަރުޙަބާ</h2>
        <div class="widget-text">…</div>
    </section>
    …
</div>
```

When the location has no widgets, it renders nothing. Add `always` to render the empty container anyway. To show something around the location only when it has widgets, use `Widgets::has()`:

```blade
@if(Widgets::has('sidebar'))
    <aside class="sidebar">
        <x-widgets::location name="sidebar" />
    </aside>
@endif
```

`Widgets::render('sidebar')` returns the same widgets as an `HtmlString`, without the container.

## The wrapper

Each widget is wrapped in `widgets::wrapper`, which gets `$widget` (the `WidgetInstance`), `$definition` (the `Widget`), `$location` and `$slot`:

```blade
{{-- resources/views/theme/widgets/card.blade.php --}}
<div class="card" id="widget-{{ $widget->id }}">
    <div class="card-body">{{ $slot }}</div>
</div>
```

Set it for one location with `->wrapper('theme.widgets.card')`, or for all of them with `config('widgets.wrapper')`.

# Rendering a location in React

Send the widgets with the page. For widgets on every page, share them from `HandleInertiaRequests`:

```php
use Javaabu\Widgets\Facades\Widgets;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'widgets' => fn () => Widgets::props(['sidebar', 'footer']),
    ];
}
```

Then render them:

```tsx
import { usePage } from "@inertiajs/react"
import { WidgetLocation, type WidgetLocations } from "@javaabu/widgets"

export function Sidebar() {
  const { widgets } = usePage<{ widgets: WidgetLocations }>().props

  return <WidgetLocation name="sidebar" widgets={widgets.sidebar} className="flex flex-col gap-6" />
}
```

`<WidgetLocation>` renders the same markup as `<x-widgets::location>`. It renders nothing when there are no widgets, unless you pass `always`. Pass `wrapper={({ widget, children }) => …}` to wrap each widget yourself.

`Widgets::toArray('sidebar')` gives one location, and `Widgets::props()` gives every location. Each widget is:

```json
{ "id": 12, "type": "text", "component": "Text", "location": "sidebar", "settings": { "title": "މަރުޙަބާ", "text": "…" }, "data": {} }
```

Settings are in the current locale. `data` is what the widget's `data()` returned.

# Reading widgets in PHP

| Call | Returns |
| --- | --- |
| `Widgets::widgets('sidebar')` | Every `WidgetInstance` in the location, in order, including widgets whose type is no longer registered |
| `Widgets::visible('sidebar')` | The widgets that render: registered types whose `shouldRender()` is true |
| `Widgets::has('sidebar')` | Whether any widget renders |
| `Widgets::render('sidebar')` | The HTML of the visible widgets |
| `Widgets::toArray('sidebar')` / `Widgets::props([...])` | The widgets for React |

A location's widgets are loaded once per request. See [Storage and caching](../advanced-usage/storage-and-caching.md) to cache them between requests.
