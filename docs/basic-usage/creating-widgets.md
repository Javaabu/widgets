---
title: Creating widgets
sidebar_position: 3
---

A widget type is a class that extends `Javaabu\Widgets\Widget`, with a Blade view and, if you render with React, a component.

# The class

```php
namespace App\Widgets;

use App\Models\Post;
use Javaabu\Stencil\Fields\Select;
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

    public function description(): ?string
    {
        return __('The latest posts, newest first.');
    }

    public function icon(): ?string
    {
        return 'newspaper';
    }

    public function settings(): array
    {
        return [
            TextInput::make('title')->label(__('Title'))->maxLength(255)->translatable(),
            TextInput::make('count')->label(__('Number of posts'))->integer()->minValue(1)->maxValue(20)->default(5)->required(),
            Select::make('category_id')->label(__('Category'))->options(fn () => Category::pluck('name', 'id')->all()),
        ];
    }

    public function data(WidgetInstance $widget): array
    {
        $posts = Post::query()
            ->published()
            ->when($widget->setting('category_id'), fn ($query, $category_id) => $query->where('category_id', $category_id))
            ->latest('published_at')
            ->limit($widget->setting('count'))
            ->get(['id', 'title', 'slug']);

        return [
            'posts' => $posts->map(fn (Post $post) => ['title' => $post->title, 'url' => route('posts.show', $post)])->all(),
        ];
    }

    public function shouldRender(WidgetInstance $widget): bool
    {
        return Post::query()->published()->exists();
    }
}
```

| Method | Default | What it's for |
| --- | --- | --- |
| `key()` | required | The unique key stored with each instance. Don't change it once widgets are saved. |
| `name()` | required | The name admins see |
| `description()` | `null` | A line about the widget on the admin page |
| `icon()` | `null` | A Lucide icon name |
| `settings()` | `[]` | The settings form: [stencil](https://github.com/Javaabu/stencil) components, with their labels, rules and defaults. Layouts (`Section`, `Grid`) work too. |
| `data(WidgetInstance $widget)` | `[]` | Extra data for the view and the React component, computed when the widget renders |
| `view()` | `widgets::widgets.{key}` | The Blade view |
| `component()` | studly key (`RecentPosts`) | The React component's name |
| `title(WidgetInstance $widget)` | the `title` setting | What the admin list calls an instance. It falls back to `name()`. |
| `shouldRender(WidgetInstance $widget)` | `true` | Return false to skip an instance, such as when it has nothing to show |

Register it in a service provider, or add it to `config('widgets.widgets')`:

```php
use App\Widgets\RecentPostsWidget;
use Javaabu\Widgets\Facades\Widgets;

Widgets::register(RecentPostsWidget::class);
```

To replace a built-in widget, register your class with the same key.

# Settings

Settings are stored as JSON on the widget instance, keyed by field name. Reading them:

```php
$widget->setting('count');                 // the value, or the field's default
$widget->setting('title');                 // translatable: the current locale, falling back to the default locale
$widget->localizedSettings();              // every setting, as the view gets them
$widget->translations('title');            // ['en' => 'Latest', 'dv' => 'އެންމެ ފަހުގެ']
```

Defaults come from the fields' `default()`, so widgets saved before you added a setting still get a value. Everything stencil offers works in `settings()`: validation rules, `visibleWhen()` conditions, `live()` fields and custom field types. Settings that hold Dhivehi should use `->thaana()` or, better, `->translatable()`; see [Translatable settings](../advanced-usage/translatable-settings.md).

# The Blade view

The view gets `$widget` (the `WidgetInstance`), `$settings` (every setting in the current locale) and each key from `data()`:

```blade
{{-- resources/views/widgets/recent-posts.blade.php --}}
@if(filled($settings['title']))
    <h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif

<ul class="widget-posts">
    @foreach($posts as $post)
        <li><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></li>
    @endforeach
</ul>
```

Point `view()` at it:

```php
public function view(): string
{
    return 'widgets.recent-posts';
}
```

Don't add a wrapper: the location's wrapper adds the `<section>` around the widget.

# The React component

The component gets `widget`, `settings` and `data`, the same values as the Blade view:

```tsx
// resources/js/widgets/RecentPosts.tsx
import type { WidgetComponentProps } from "@javaabu/widgets"

type Settings = { title?: string | null; count: number }
type Data = { posts: { title: string; url: string }[] }

export function RecentPosts({ settings, data }: WidgetComponentProps<Settings, Data>) {
  return (
    <>
      {settings.title ? <h2 className="widget-title">{settings.title}</h2> : null}
      <ul className="widget-posts">
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

Register it once, where your app starts, by widget key or component name:

```tsx
// resources/js/app.tsx
import { registerWidgets } from "@javaabu/widgets"
import { RecentPosts } from "./widgets/RecentPosts"

registerWidgets({ "recent-posts": RecentPosts })
```

Or pass it to one location: `<WidgetLocation name="sidebar" widgets={…} components={{ RecentPosts }} />`. A widget without a component is skipped, with a warning in development.

Keep the Blade view and the component's markup the same, so a page looks the same in either stack.

# Testing a widget

```php
use App\Widgets\RecentPostsWidget;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;

public function test_it_lists_recent_posts(): void
{
    Widgets::location('sidebar');
    Post::factory()->published()->create(['title' => 'ޚަބަރު']);

    WidgetInstance::create(['location' => 'sidebar', 'type' => 'recent-posts', 'settings' => ['count' => 3]]);

    $this->assertStringContainsString('ޚަބަރު', Widgets::render('sidebar')->toHtml());
    $this->assertEquals('ޚަބަރު', Widgets::toArray('sidebar')[0]['data']->posts[0]['title']);
}
```

To test the form, post to the admin route as a user with `edit_widgets`: `$this->post('/widgets/sidebar?type=recent-posts', ['count' => '3'])`.
