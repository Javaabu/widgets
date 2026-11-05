---
title: Controllers, policies and views
sidebar_position: 3
---

# The controller

Extend `Javaabu\Widgets\Http\Controllers\WidgetsController` and point the config at it, or pass `controller` to `Route::widgets()`:

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\Widgets\Http\Controllers\WidgetsController as BaseController;
use Javaabu\Widgets\Models\WidgetInstance;

class WidgetsController extends BaseController
{
    protected function saved(Request $request, WidgetInstance $widget): void
    {
        activity()->performedOn($widget)->causedBy($request->user('web_admin'))->log('widget saved');
    }

    protected function deleted(Request $request, WidgetInstance $widget): void
    {
        cache()->tags('pages')->flush();
    }
}
```

```php
// config/widgets.php
'controllers' => ['widgets' => App\Http\Controllers\Admin\WidgetsController::class],
```

| Method to override | |
| --- | --- |
| `saved(Request, WidgetInstance)` | After a widget is added or saved |
| `deleted(Request, WidgetInstance)` | After a widget is removed |
| `form(Request, $widget, WidgetLocation)` | The `WidgetForm` used to edit and save |
| `editData(...)`, `locationsData(Request)`, `widgetData(...)` | What the pages get |
| `strings()` | The strings the pages show |

# The policy

The default policy is `Javaabu\Widgets\Policies\WidgetPolicy`. Override it in any of these ways; the first one that applies wins:

1. `config('permissions.policies')` from javaabu/permissions
2. `Gate::policy(WidgetLocation::class, …)` and `Gate::policy(WidgetInstance::class, …)` in your app
3. `config('widgets.policy')`

```php
use Javaabu\Widgets\Policies\WidgetPolicy;
use Javaabu\Widgets\WidgetLocation;

class ThemePolicy extends WidgetPolicy
{
    public function manage(Model $user, WidgetLocation $location): bool
    {
        if ($location->getKey() === 'home_top') {
            return $user->hasRole('editor');
        }

        return parent::manage($user, $location);
    }
}
```

# Blade views

Publish them with `php artisan vendor:publish --tag=widgets-views` and edit the copies in `resources/views/vendor/widgets`:

| View | |
| --- | --- |
| `pages/index.blade.php`, `partials/location.blade.php` | The admin index |
| `pages/edit.blade.php` | The settings form page |
| `components/location.blade.php` | `<x-widgets::location>` |
| `wrapper.blade.php` | The wrapper around each widget |
| `widgets/text.blade.php`, `image`, `link` | The built-in widgets |

The index page relies on these attributes for drag and drop: `data-widgets-admin`, `data-strings`, `data-widgets-announcer`, `form[data-widgets-order]`, `[data-widgets-sortable]`, `[data-widget-id]` with `data-widget-title`, `[data-widgets-handle]` and the hidden `widgets[]` inputs. Keep them if you change the markup. `data-widgets-confirm` on a form asks before submitting it.

# React pages and components

Override a single file with manifest; your copy wins wherever it's imported:

```bash
php artisan manifest:override widgets js/Pages/Widgets/Index.tsx
php artisan manifest:override widgets js/components/WidgetsLocationCard.tsx
```

Or build your own page from the exported parts: `WidgetsLocationCard`, `SortableList`, `WidgetsIndex` and `WidgetsEdit`. See [JavaScript API](javascript-api.md).
