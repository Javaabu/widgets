---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- [javaabu/stencil](https://github.com/Javaabu/stencil): widget settings forms (installed with the package)
- [javaabu/forms](https://github.com/Javaabu/forms) v2: the Blade admin pages (installed with the package)
- [javaabu/permissions](https://github.com/Javaabu/permissions) v3: permissions and the policy (installed with the package)
- [javaabu/manifest](https://github.com/Javaabu/manifest): ships the admin script, the widget styles and the React code to your app (installed with the package)

Optional:

- [inertiajs/inertia-laravel](https://inertiajs.com) v2, for the Inertia React admin pages (`widgets.views` = `inertia`)
- [javaabu/translatable](https://github.com/Javaabu/translatable) v3, for translatable widget settings. See [Translatable settings](advanced-usage/translatable-settings.md).

Blade apps need no npm packages for widgets beyond what manifest asks for to build assets (`vite`, `laravel-vite-plugin`). The admin script imports nothing from npm.

For React (the `<WidgetLocation>` component and the Inertia pages), your app needs React 19, `@inertiajs/react`, [@javaabu/forms](https://github.com/Javaabu/react-forms), [@javaabu/onu](https://www.npmjs.com/package/@javaabu/onu) and manifest's Vite plugin, which resolves `@javaabu/widgets` and `@javaabu/stencil`. The package declares these for manifest's pages scope, so `php artisan manifest:check` asks for them only when your app uses Inertia (or with `--scope=pages`).
