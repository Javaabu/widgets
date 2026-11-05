---
title: Permissions
sidebar_position: 5
---

The package registers a **Widgets** permission group with [javaabu/permissions](https://github.com/Javaabu/permissions), and a policy for locations and widgets. Run `php artisan permissions:sync` after installing, and after adding a location with its own permission.

| Permission | Lets the user |
| --- | --- |
| `view_widgets` | See the admin page and every location's widgets |
| `edit_widgets` | Add, configure, order and remove widgets in every location that doesn't have its own permission |
| a location's permission (`->permission('edit_footer_widgets')`) | Do the same in that location. `edit_widgets` doesn't cover it. |

```php
$role->givePermissionTo(['view_widgets', 'edit_widgets', 'edit_footer_widgets']);
```

# The policy

`Javaabu\Widgets\Policies\WidgetPolicy` covers `WidgetLocation` and the widget model:

| Ability | Argument | Allowed with |
| --- | --- | --- |
| `viewAny` | `WidgetLocation::class` | `view_widgets`, `edit_widgets`, or any location's permission |
| `view` | a `WidgetLocation` | `view_widgets`, or what `manage` needs |
| `manage`, `create`, `reorder` | a `WidgetLocation` | the location's permission, else `edit_widgets` |
| `update`, `delete` | a `WidgetInstance` | `manage` on the widget's location |

Use it in your own code like any policy:

```php
use Javaabu\Widgets\Facades\Widgets;

$user->can('manage', Widgets::findLocation('footer'));
```

```blade
@can('manage', Widgets::findLocation('sidebar'))
    <a href="{{ route('admin.widgets.index') }}">{{ __('Edit widgets') }}</a>
@endcan
```

To change the rules, see [Controllers, policies and views](../advanced-usage/controllers-and-views.md#the-policy).

# Guards and user models

Nothing assumes the `web` guard or `App\Models\User`. `Route::widgets(['guard' => 'web_admin'])` authenticates with that guard and checks that guard's user, and permissions are synced for every guard in `config('permissions.guards')`. Register the routes once per guard if several kinds of users manage widgets.

Set `widgets.register_permissions` to `false` to register the permissions yourself.
