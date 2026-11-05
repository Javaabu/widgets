---
title: Introduction
sidebar_position: 1.0
---

# Widgets

[Widgets](https://github.com/Javaabu/widgets) brings WordPress classic-style widgets to Laravel. Developers decide **where** widgets can go (a sidebar, the footer columns) and **which** widgets exist. Admins then add widgets to each location, put them in order, configure them and remove them, from pages the package provides.

A widget is a PHP class:

- its **settings** are [javaabu/stencil](https://github.com/Javaabu/stencil) components, so the same definition gives the admin form, validation and stored values;
- it **renders** with a Blade view and a React component, which get the same settings and data;
- settings marked `->translatable()` get one value per language when [javaabu/translatable](https://github.com/Javaabu/translatable) is installed.

```php
use Javaabu\Widgets\Facades\Widgets;

// AppServiceProvider::boot()
Widgets::register(RecentPostsWidget::class);
Widgets::location('sidebar', fn () => __('Sidebar'))->max(6);
Widgets::location('footer', fn () => __('Footer'))->allow([TextWidget::class, LinkWidget::class]);

// routes/web.php
Route::widgets(['guard' => 'web_admin', 'prefix' => 'admin/widgets', 'name' => 'admin.widgets']);
```

```blade
{{-- resources/views/layouts/app.blade.php --}}
<x-widgets::location name="sidebar" />
```

```tsx
// An Inertia layout
<WidgetLocation name="sidebar" widgets={widgets.sidebar} />
```

# What's included

- **Locations**: `Widgets::location()`, with allowed widgets, a maximum, their own permission and a wrapper. They render with `<x-widgets::location>` in Blade and `<WidgetLocation>` in React. See [Locations](basic-usage/locations.md).
- **Built-in widgets**: Text, Image and Link. Blade and React give the same markup. See [Built-in widgets](basic-usage/built-in-widgets.md).
- **Your own widgets**: a class, a Blade view and a React component. See [Creating widgets](basic-usage/creating-widgets.md).
- **Admin pages**: `Route::widgets()` adds the pages in Blade (javaabu/forms) or Inertia React (@javaabu/forms and Onu). You can drag a widget's handle to reorder, or use the keyboard: Space, the arrow keys, then Space again. See [Admin pages](basic-usage/admin-pages.md).
- **Permissions**: `view_widgets`, `edit_widgets` and per-location permissions, registered with [javaabu/permissions](https://github.com/Javaabu/permissions). The policy can be overridden, and the routes work with any guard. See [Permissions](basic-usage/permissions.md).
- **Dhivehi and RTL**: every string is in English and Dhivehi, and the views use logical CSS. See [Dhivehi and RTL](advanced-usage/dhivehi-and-rtl.md).
