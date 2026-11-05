# Widgets

[![Tests](https://github.com/Javaabu/widgets/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Javaabu/widgets/actions/workflows/tests.yml?query=branch%3Amain)
[![codecov](https://codecov.io/gh/Javaabu/widgets/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/widgets)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/widgets.svg)](https://packagist.org/packages/javaabu/widgets)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

WordPress classic-style widgets for Laravel. A widget is a class: its settings are a [javaabu/stencil](https://github.com/Javaabu/stencil) form and it renders with a Blade view and a React component. Developers define where widgets go (sidebars, footers); admins add, order, configure and remove them.

- Widget locations rendered with `<x-widgets::location name="sidebar" />` or `<WidgetLocation widgets={…} />`.
- Built-in Text, Image and Link widgets, with the same markup in Blade and React.
- Admin pages from `Route::widgets()`, in Blade or Inertia React, with keyboard-accessible drag and drop.
- A policy and permissions through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model.
- Dhivehi and RTL; translatable settings with [javaabu/translatable](https://github.com/Javaabu/translatable).

```php
use Javaabu\Widgets\Facades\Widgets;

// AppServiceProvider::boot()
Widgets::register(RecentPostsWidget::class);
Widgets::location('sidebar', fn () => __('Sidebar'))->max(6);

// routes/web.php
Route::widgets(['guard' => 'web_admin', 'prefix' => 'admin/widgets', 'name' => 'admin.widgets']);
```

```blade
<x-widgets::location name="sidebar" />
```

## Documentation

See the [docs](docs/intro.md), or [llms.txt](llms.txt) for AI assistants.

## License

The MIT License (MIT). See [License File](LICENSE.md).
