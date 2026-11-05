# widgets: notes for AI coding agents

`javaabu/widgets` is a Laravel package for WordPress classic-style widgets. Widget types are PHP classes (`Widget`) with javaabu/stencil settings, a Blade view and a React component. Developers define locations; admins manage widget instances on `Route::widgets()` pages in Blade or Inertia React, with keyboard-accessible drag and drop. It also ships TypeScript (imported by apps as `@javaabu/widgets` through javaabu/manifest) and a prebuilt `dist/` for the Blade admin script and styles.

Treat `Widget`, `WidgetLocation`, `WidgetsManager`/`Widgets`, `WidgetInstance`, `WidgetForm`, `WidgetsController`, `WidgetPolicy`, `Route::widgets()`, the page props and the `@javaabu/widgets` exports as public contracts: javaabu/cms builds on them.

## Layout

| Path | What |
| --- | --- |
| `src/Widget.php`, `src/Widgets/` | The base class and the built-in Text, Image and Link widgets |
| `src/WidgetLocation.php`, `src/WidgetsManager.php`, `src/Facades/Widgets.php` | Locations and the registry; loading, rendering, serializing, cache |
| `src/Models/WidgetInstance.php`, `database/migrations` | Stored widgets (`widget_instances`) |
| `src/Forms/WidgetForm.php` | The stencil form: settings, the move select, translatable settings, saving |
| `src/Routing`, `src/Http`, `src/Policies`, `src/Views` | `Route::widgets()`, the controller, the policy, `ViewRenderer`, `<x-widgets::location>` |
| `src/Support` | `Translations` (optional javaabu/translatable, feature-detected), `Url` (safe links) |
| `resources/views` | Blade: admin pages (javaabu/forms), the location component, the wrapper, the built-in widgets |
| `resources/js` | TypeScript: `WidgetLocation` and the registry, built-in widget components, `SortableList`, `createSortable` (`sortable.ts`), the Inertia pages, the Blade entry `blade/admin.ts` |
| `resources/css/widgets.css` | Optional widget styles (logical properties only) |
| `dist/` | Prebuilt Blade script and styles, **committed** |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `tests/Fixtures/rendering` | Built-in widgets: serialized widgets and Blade HTML, checked by PHP and React |
| `tests/js/fixtures` | Inertia page props written by `InertiaPagesTest` |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Laravel Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…`, early returns, no `else`. Pint enforces formatting.
- A built-in widget's Blade view and React component must render the same markup. After changing either, run `WIDGETS_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter RenderingFixturesTest`, review the diff, and make `pnpm test` pass.
- When the Inertia props change, run `WIDGETS_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter InertiaPagesTest` and commit `tests/js/fixtures`.
- After changing `resources/js/blade`, `resources/js/sortable.ts` or `resources/css`, run `pnpm build` and commit `dist/`. CI fails when it's stale.
- Safe-link logic is in PHP (`Support/Url`) and TS (`url.ts`). Change both together.
- Nothing may assume the `web` guard or `App\Models\User`; HTTP tests use two guards (`web` → `User`, `web_admin` → `Admin`).
- Inertia and javaabu/translatable are optional: feature-detect them (translatable only through `Support\Translations`), keep them in `suggest`, and keep test classes loadable without them. CI has a leg without both.
- Every user-facing string goes through `__()` with an English key and gets a line in `lang/en.json` and `lang/dv.json` (`LangTest` checks). Use Thaana in tests of user-facing behaviour. List uncertain translations in `docs/advanced-usage/dhivehi-and-rtl.md`. Views use logical CSS only.
- Every change needs tests, docs (linked from `llms.txt`, checked by `DocsTest`), the Boost skill if usage changes, and a line in `CHANGELOG.md`.
- Work on `main`. Never tag or release.

## Commands

```bash
composer install && pnpm install      # pnpm 10.28 (packageManager); composer first (stencil's React code is in vendor/)
composer lint && composer test        # Pint and PHPUnit
pnpm lint && pnpm format:check && pnpm typecheck && pnpm test
pnpm build                            # dist/
pnpm examples                         # http://localhost:5173
```
