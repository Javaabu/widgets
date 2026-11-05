# javaabu/widgets API

## Widget (abstract, `Javaabu\Widgets\Widget`)

| Method | Default |
| --- | --- |
| `static key(): string` | required: stored on each instance |
| `name(): string` | required |
| `description(): ?string`, `icon(): ?string` (Lucide) | `null` |
| `settings(): array` | `[]`: stencil components |
| `data(WidgetInstance $widget): array` | `[]`: extra view and React props |
| `view(): string` | `widgets::widgets.{key}` |
| `component(): string` | `Str::studly(key)` |
| `title(WidgetInstance $widget): ?string` | the `title` setting |
| `shouldRender(WidgetInstance $widget): bool` | `true` |
| `fields()`, `translatableSettings()`, `defaults()`, `viewData()`, `render()`, `toArray()` | helpers |

Built-ins: `TextWidget` (`text`: title, text), `ImageWidget` (`image`: title, src, alt, caption, link), `LinkWidget` (`link`: title, description, label, url, new_tab), in `Javaabu\Widgets\Widgets`.

## WidgetLocation

`Widgets::location($key, string|Closure $label = null)` → `->label()`, `->description()`, `->allow([classes or keys])`, `->deny([...])`, `->max(?int)`, `->permission(?string)`, `->wrapper(?string $view)`, `->order(int)`, `->allows($widget)`, `->toArray()`.

## Widgets facade

`register()`, `unregister()`, `all()`, `find()`, `findOrFail()`, `exists()`, `available($location)`, `location()`, `locations()`, `findLocation()`, `hasLocation()`, `removeLocation()`, `model()`, `widgets($location)`, `visible($location)`, `has($location)`, `render($location)`, `renderWidget($widget)`, `toArray($location)`, `props(?array $locations)`, `widgetToArray($widget)`, `flush()`, `Widgets::ignoreMigrations()`.

## WidgetInstance (`config('widgets.model')`, table `widget_instances`)

Columns `location`, `type`, `settings` (json), `order`. Scopes `inLocation()`, `ordered()`. `setting($key, $default)`, `localizedSettings(?$locale)`, `translations($key)`, `definition()`, `getLocation()`, `displayTitle()`.

## WidgetForm (`Javaabu\Widgets\Forms\WidgetForm`, a stencil Form)

`WidgetForm::for($widget_or_type, $location = null, $guard = null)`, `->saveWidget($request_or_array)`, `->settingsFrom($data, $stored)`, `->locationOptions()`. The move field is `_location` (`WidgetForm::LOCATION`).

## Routes (`Route::widgets($options)`)

Options: `guard`, `prefix` (`widgets`), `name` (`widgets`), `middleware`, `controller`.

| Route | Name |
| --- | --- |
| `GET {prefix}` | `.index` |
| `GET {prefix}/{location}/create?type=` | `.create` |
| `POST {prefix}/{location}?type=` | `.store` |
| `PUT {prefix}/{location}/order` (`widgets[]`) | `.order` |
| `GET {prefix}/{location}/{widget}/edit` | `.edit` |
| `PATCH {prefix}/{location}/{widget}` (`_location` moves) | `.update` |
| `DELETE {prefix}/{location}/{widget}` | `.destroy` |

JSON responses when the request expects JSON.

## Permissions and policy

`view_widgets`, `edit_widgets`, plus each location's `->permission()`. `WidgetPolicy` abilities: `viewAny`, `view`/`manage`/`create`/`reorder` (WidgetLocation), `update`/`delete` (WidgetInstance).

## Config (`config/widgets.php`)

`widgets`, `locations`, `model`, `table`, `cache.enabled|store|prefix`, `wrapper`, `views` (`blade`|`inertia`), `layout`, `layout_section`, `controllers.widgets`, `policy`, `register_permissions`.

## React (`@javaabu/widgets`)

`WidgetLocation`, `Widget`, `registerWidgets`, `registeredWidgets`, `resolveWidget`, `builtInWidgets`, `TextWidget`, `ImageWidget`, `LinkWidget`, `WidgetsIndex`, `WidgetsEdit`, `WidgetsLocationCard`, `SortableList`, `createSortable`, `moveItem`, `announcement`, `keyboardTarget`, `format`, `safeUrl`, `isSafeUrl`, `isExternalUrl`, `filled`; types `WidgetData`, `WidgetLocations`, `WidgetComponentProps`, `WidgetsIndexProps`, `WidgetsEditProps`.
