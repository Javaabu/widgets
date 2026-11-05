# Changelog

All notable changes to `javaabu/widgets` are documented in this file.

## Unreleased

Initial development of v1.0:

- Widget types as classes (`Widget`): settings as javaabu/stencil components, a Blade view, a React component, `data()` and `shouldRender()`. Built-in Text, Image and Link widgets with the same markup in Blade and React.
- Locations (`Widgets::location()`): allowed and denied widgets, a maximum, their own permission, a wrapper view and an order. Rendered with `<x-widgets::location>` and `<WidgetLocation>`; `Widgets::has()`, `render()`, `toArray()` and `props()`.
- `WidgetInstance` model and `widget_instances` table; widgets loaded once per request per location, with an optional cache.
- Admin pages from `Route::widgets()` in Blade (javaabu/forms) or Inertia React (@javaabu/forms, Onu): add, configure, move, remove, and reorder by drag and drop or the keyboard, with live-region announcements. JSON responses for every route.
- Overridable controller and policy; `view_widgets`, `edit_widgets` and per-location permissions through javaabu/permissions; any guard.
- Optional translatable settings with javaabu/translatable (one input per language, stored as locale maps, current-locale rendering with fallback).
- Safe links (`SafeUrl` rule, `Url`, `safeUrl()`).
- English and Dhivehi strings, RTL-safe views and styles.
- Assets through javaabu/manifest with a prebuilt `dist/`; runnable examples; docs, `llms.txt` and the Laravel Boost guideline and skill.
