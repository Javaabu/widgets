---
title: Admin pages
sidebar_position: 4
---

`Route::widgets()` adds the pages where admins manage widgets. Nothing is registered until you call it, so you choose the prefix, middleware and guard.

```php
// routes/web.php: the default guard
Route::widgets();

// routes/admin.php: another guard
Route::widgets([
    'guard' => 'web_admin',              // auth:web_admin, and permissions are checked for this guard's user
    'prefix' => 'admin/widgets',
    'name' => 'admin.widgets',
    'middleware' => ['web', 'auth:web_admin', 'verified'],
    'controller' => App\Http\Controllers\Admin\WidgetsController::class,
]);
```

| Option | Default | |
| --- | --- | --- |
| `guard` | `null` (the default guard) | The guard whose user is authorized |
| `prefix` | `widgets` | The URL prefix |
| `name` | `widgets` | The route name prefix |
| `middleware` | `['auth']`, or `["auth:{guard}"]` | Replaces the default middleware |
| `controller` | `config('widgets.controllers.widgets')` | A subclass of `WidgetsController` |

# Routes

| Method and URL | Name | What |
| --- | --- | --- |
| `GET {prefix}` | `{name}.index` | Every location the user can see, with its widgets |
| `GET {prefix}/{location}/create?type={key}` | `{name}.create` | The settings form for a new widget |
| `POST {prefix}/{location}?type={key}` | `{name}.store` | Adds the widget at the end of the location |
| `PUT {prefix}/{location}/order` | `{name}.order` | Puts the location's widgets in the order of `widgets[]` (ids) |
| `GET {prefix}/{location}/{widget}/edit` | `{name}.edit` | The settings form of a widget |
| `PATCH {prefix}/{location}/{widget}` | `{name}.update` | Saves the settings. Sending `_location` moves the widget to the end of another location. |
| `DELETE {prefix}/{location}/{widget}` | `{name}.destroy` | Removes the widget |

Every route answers with JSON when the request expects it (`Accept: application/json`). `index` returns `{ data: [locations] }`. `store` returns `201` with `{ message, widget }`, `update` returns `{ message, widget }`, and `order` returns `{ message, widgets: [ids] }`. Validation errors are `422`, as usual. On the web, each action redirects to the index with a `status` message.

# The index page

Each location shows its widgets in order. Users who may change the location also see:

- a **handle** on each widget, for reordering;
- **Edit** and **Remove** buttons (Remove asks first);
- **Add a widget**: a list of the widgets allowed in the location. It's hidden when the location is full.

Widgets whose type is no longer registered are listed, marked "This widget type is no longer available", so they can be removed.

## Reordering

The order is saved as soon as a widget is dropped.

- **Mouse or touch:** drag a widget by its handle.
- **Keyboard:** Tab to a widget's handle and press Space or Enter to pick it up. Move it with the arrow keys (Home and End jump to the start and the end), then press Space or Enter to drop it. Escape puts it back, and moving focus away drops it.
- **Screen readers:** the handle is described by the instructions on the page. Each step is announced in a live region, such as "Text picked up. Position 1 of 3." and "Text moved to position 2 of 3.". The announcement also says when the order is saved, or that it couldn't be saved.

In right-to-left pages, the left and right arrow keys follow the reading direction.

To move a widget to another location, open it and change **Location**. The list offers the locations where the widget is allowed and the user may edit widgets. A full location refuses it.

# The settings form

The form is the widget's `settings()`, built with stencil's Blade driver, or `<StencilForm>` in React. When editing, it starts with the **Location** select, if there's another location to move to. Validation comes from the fields: the built-in widgets require their main setting and check links. Translatable settings have one input per language; see [Translatable settings](../advanced-usage/translatable-settings.md).

A full location refuses new widgets with "Footer holds only 3 widgets." on the `type` field.

# Blade or Inertia React

`config('widgets.views')` (`WIDGETS_VIEWS`) picks the stack:

| | Blade (`blade`, default) | Inertia React (`inertia`) |
| --- | --- | --- |
| Index | `widgets::pages.index` | `widgets::Widgets/Index` |
| Create and edit | `widgets::pages.edit` | `widgets::Widgets/Edit` |
| Built with | javaabu/forms v2 components; stencil's Blade driver; `widgets::admin` script | @javaabu/forms, Onu, `<StencilForm>` from @javaabu/stencil, `SortableList` |

The Blade pages extend `config('widgets.layout')` (`layouts.app`) and fill `config('widgets.layout_section')` (`content`). Both stacks get the same props:

```ts
// widgets::Widgets/Index
{ locations: WidgetLocationData[], status: string | null, strings: WidgetsStrings }

// widgets::Widgets/Edit
{ form: StencilSchema, location, widget: WidgetTypeData, instance: WidgetItemData | null, index_url: string, strings: WidgetsStrings }
```

The types are exported from `@javaabu/widgets`. See [Controllers, policies and views](../advanced-usage/controllers-and-views.md) to change the pages.
