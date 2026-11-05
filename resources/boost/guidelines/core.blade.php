{{--
    javaabu/widgets guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

@verbatim
## javaabu/widgets

- Sidebars, footers and other admin-managed areas are widget locations: define them in a service provider with `Widgets::location('sidebar', fn () => __('Sidebar'))` (`->allow([...])`, `->max(6)`, `->permission('edit_sidebar_widgets')`) and render them with `<x-widgets::location name="sidebar" />` (Blade) or `<WidgetLocation name="sidebar" widgets={widgets.sidebar} />` from `@javaabu/widgets` (React, with `Widgets::props([...])` shared from the controller or `HandleInertiaRequests`).
- A widget type extends `Javaabu\Widgets\Widget`: static `key()`, `name()`, `settings()` returning javaabu/stencil components (mark per-language text `->translatable()`), optional `data()`, a Blade view and a React component registered with `registerWidgets({ key: Component })`. Register it with `Widgets::register()`.
- Admin pages come from `Route::widgets(['guard' => ..., 'prefix' => ..., 'name' => ...])`; permissions are `view_widgets`, `edit_widgets` and location permissions (run `php artisan permissions:sync`). Never hand-write widget CRUD routes or loop over `WidgetInstance` models in views.
- Always activate the `javaabu-widgets` skill when adding widget locations, writing widgets, rendering widgets, or setting up or changing the widget admin pages.
@endverbatim
