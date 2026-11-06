# Common mistakes with javaabu/widgets

- **Rendering instances by hand.** `@foreach(WidgetInstance::where('location', 'sidebar')->get() as $widget)` skips the ordering, wrappers, `shouldRender()`, translations and caching. Use `<x-widgets::location name="sidebar" />` or `Widgets::render('sidebar')`.
- **Reading `$widget->settings['title']`.** Translatable settings are stored as `['en' => …, 'dv' => …]`. Use `$widget->setting('title')` or the view's `$settings`.
- **Labels as plain strings in a provider.** `Widgets::location('sidebar', __('Sidebar'))` translates once, at boot, in the boot locale. Pass a closure: `fn () => __('Sidebar')`.
- **Changing a widget's `key()`.** Saved widgets keep the old key and stop rendering. Keep the key, or migrate the `type` column.
- **A wrapper in the widget view.** The location wraps every widget in `<section class="widget widget-{type}">`. Change the wrapper with `->wrapper()` or `config('widgets.wrapper')`, not in each view.
- **Forgetting the React component.** Inertia apps render widgets with React; a widget without `registerWidgets({ key: Component })` is skipped (with a console warning in development).
- **Unchecked links.** `TextInput::make('url')` alone accepts `javascript:` URLs. Add `->rules([new SafeUrl])` and render with `Url::safe()`.
- **Importing the widget CSS in an Inertia app.** `<WidgetLocation>` and the built-in components already import `resources/css/widgets.css`. To restyle, override it with `php artisan manifest:override widgets css/widgets.css` or use the `--widgets-button-*` variables.
- **Installing React packages for a Blade app.** The Blade admin script needs nothing from npm; `manifest:check` only lists React packages for Inertia apps.
- **Writing rows without the model.** The cache is cleared on model events. After a query builder update, call `Widgets::flush()`.
- **Assuming the web guard.** Pass `'guard' => 'web_admin'` to `Route::widgets()` for admin users, and run `permissions:sync` so the permissions exist for that guard.
- **A location permission nobody has.** `->permission('edit_footer_widgets')` replaces `edit_widgets` for that location; give it to a role.
