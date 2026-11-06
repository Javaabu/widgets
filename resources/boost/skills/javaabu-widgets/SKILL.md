---
name: javaabu-widgets
description: Add WordPress classic-style widgets to a Laravel app with javaabu/widgets. Use when defining widget locations (Widgets::location, sidebars, footers), rendering them in Blade (<x-widgets::location>) or React (<WidgetLocation> from @javaabu/widgets, Widgets::props), writing a widget type (extends Javaabu\Widgets\Widget with stencil settings, a Blade view and a React component), setting up the admin pages (Route::widgets, view_widgets/edit_widgets permissions, Blade or Inertia), translatable widget settings, or overriding the controller, policy or views.
license: MIT
metadata:
  author: Javaabu
---

# Widgets with javaabu/widgets

The docs ship with the package: `vendor/javaabu/widgets/llms.txt` lists every page in `vendor/javaabu/widgets/docs/`.

| Task | Read |
| --- | --- |
| Locations, a custom widget, the admin pages | This file |
| Every method, option, route, prop and config key | [references/api.md](references/api.md) |
| Mistakes to avoid | [references/common-mistakes.md](references/common-mistakes.md) |

## 1. Define locations

```php
// app/Providers/AppServiceProvider.php
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Widgets\LinkWidget;
use Javaabu\Widgets\Widgets\TextWidget;

public function boot(): void
{
    Widgets::location('sidebar', fn () => __('Sidebar'))->max(6);

    Widgets::location('footer', fn () => __('Footer'))
        ->allow([TextWidget::class, LinkWidget::class])
        ->max(3)
        ->permission('edit_footer_widgets');
}
```

## 2. Render them

```blade
@if(Widgets::has('sidebar'))
    <aside>
        <x-widgets::location name="sidebar" class="flex flex-col gap-6" />
    </aside>
@endif
```

For Inertia, share the widgets and render them with the React component:

```php
// app/Http/Middleware/HandleInertiaRequests.php
'widgets' => fn () => Widgets::props(['sidebar', 'footer']),
```

```tsx
import { usePage } from "@inertiajs/react"
import { WidgetLocation, type WidgetLocations } from "@javaabu/widgets"

const { widgets } = usePage<{ widgets: WidgetLocations }>().props

<WidgetLocation name="sidebar" widgets={widgets.sidebar} />
```

Styles: in Blade, add `@manifestRequire('widgets::styles')` where widgets render. In React, `<WidgetLocation>` and the built-in widgets import the stylesheet themselves, so don't import `widgets.css` in the app. React apps need `@javaabu/forms`, `@javaabu/onu`, React and `@inertiajs/react` (`php artisan manifest:check` lists them for Inertia apps); Blade apps need none of them.

## 3. Add the admin pages

```php
// routes/admin.php
Route::widgets(['guard' => 'web_admin', 'prefix' => 'admin/widgets', 'name' => 'admin.widgets']);
```

Give the admin role `edit_widgets` (and `edit_footer_widgets`), then run `php artisan permissions:sync`. Set `WIDGETS_VIEWS=inertia` for the React pages (`widgets::Widgets/Index`, `widgets::Widgets/Edit`).

## 4. Write a widget

```php
namespace App\Widgets;

use App\Models\Post;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Widget;

class RecentPostsWidget extends Widget
{
    public static function key(): string
    {
        return 'recent-posts';
    }

    public function name(): string
    {
        return __('Recent posts');
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            TextInput::make('count')->label(__('Number of posts'))->integer()->minValue(1)->default(5)->required(),
        ];
    }

    public function data(WidgetInstance $widget): array
    {
        return [
            'posts' => Post::query()->latest()->limit($widget->setting('count'))->get()
                ->map(fn (Post $post) => ['title' => $post->title, 'url' => route('posts.show', $post)])
                ->all(),
        ];
    }

    public function view(): string
    {
        return 'widgets.recent-posts';
    }
}
```

```blade
{{-- resources/views/widgets/recent-posts.blade.php: gets $widget, $settings and data() --}}
@if(filled($settings['title']))
    <h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
<ul>
    @foreach($posts as $post)
        <li><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></li>
    @endforeach
</ul>
```

```tsx
// resources/js/widgets/RecentPosts.tsx: the same markup
import type { WidgetComponentProps } from "@javaabu/widgets"

export function RecentPosts({ settings, data }: WidgetComponentProps<{ title?: string }, { posts: { title: string; url: string }[] }>) {
  return (
    <>
      {settings.title ? <h2 className="widget-title">{settings.title}</h2> : null}
      <ul>
        {data.posts.map((post) => (
          <li key={post.url}>
            <a href={post.url}>{post.title}</a>
          </li>
        ))}
      </ul>
    </>
  )
}
```

```php
Widgets::register(RecentPostsWidget::class);        // AppServiceProvider::boot()
```

```ts
registerWidgets({ "recent-posts": RecentPosts })    // resources/js/app.tsx
```

## Rules

- Follow Javaabu codestyle: snake_case variables, camelCase methods, early returns, `__()` for every label (and add it to `lang/dv.json`).
- Read settings with `$widget->setting()` or the view's `$settings`, never `$widget->settings`, which holds raw locale maps for translatable settings.
- Links in settings: `->rules([new \Javaabu\Widgets\Rules\SafeUrl])`, and render with `Url::safe()` / `safeUrl()`.
- Change behaviour by extending `WidgetsController` or `WidgetPolicy`, not by writing your own widget routes.
