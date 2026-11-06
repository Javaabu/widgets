---
title: Built-in widgets
sidebar_position: 2
---

Three widgets are registered by default, in `config('widgets.widgets')`. Remove one from the config, or call `Widgets::unregister(ImageWidget::class)`, to hide it.

All of them render the same markup in Blade (`resources/views/widgets/*.blade.php`) and React (`TextWidget`, `ImageWidget` and `LinkWidget` from `@javaabu/widgets`). The optional `widgets::styles` stylesheet styles them with logical properties.

# Text (`text`)

`Javaabu\Widgets\Widgets\TextWidget`: a title and some text. Line breaks in the text are kept (`white-space: pre-line`), and HTML is escaped.

| Setting | Field | Rules |
| --- | --- | --- |
| `title` | text, translatable | up to 255 characters |
| `text` | textarea, translatable | required, up to 5000 characters |

```html
<h2 class="widget-title">މަރުޙަބާ</h2>
<div class="widget-text">ފުރަތަމަ ބައި
ދެވަނަ ބައި</div>
```

# Image (`image`)

`Javaabu\Widgets\Widgets\ImageWidget`: an image from a URL, with alternative text, a caption and an optional link.

| Setting | Field | Rules |
| --- | --- | --- |
| `title` | text, translatable | up to 255 characters |
| `src` | text | required, a [safe link](#safe-links) |
| `alt` | text, translatable | up to 255 characters |
| `caption` | text, translatable | up to 255 characters |
| `link` | text | a [safe link](#safe-links) |

```html
<h2 class="widget-title">Malé</h2>
<figure class="widget-image">
    <a href="/male"><img src="https://example.com/male.jpg" alt="މާލެ" loading="lazy"></a>
    <figcaption>ރާއްޖޭގެ ވެރިރަށް</figcaption>
</figure>
```

# Link (`link`)

`Javaabu\Widgets\Widgets\LinkWidget`: a call to action, with a title, a short description and a button that links somewhere.

| Setting | Field | Rules |
| --- | --- | --- |
| `title` | text, translatable | up to 255 characters |
| `description` | textarea, translatable | up to 1000 characters |
| `label` | text, translatable | required: the button text |
| `url` | text | required, a [safe link](#safe-links) |
| `new_tab` | checkbox | opens the link in a new tab, with `rel="noopener noreferrer"` |

```html
<h2 class="widget-title">ގުޅުއްވާ</h2>
<p class="widget-description">Get in touch</p>
<a class="widget-button" href="https://example.com/contact" rel="noopener">ގުޅުއްވާ</a>
```

External links get `rel="noopener"`.

# Safe links

Link settings accept `https://` and `http://` URLs, `mailto:` and `tel:` links, paths starting with `/`, and `#fragments`. The `Javaabu\Widgets\Rules\SafeUrl` rule checks this when a widget is saved. `Javaabu\Widgets\Support\Url` checks it again when the widget renders, in PHP, and `safeUrl()` from `@javaabu/widgets` does the same in React. So a `javascript:` link never reaches the page, even when it was written to the database directly. An image or link widget with an unsafe URL doesn't render. Use the same rule in your own widgets:

```php
TextInput::make('url')->label(__('Link'))->rules([new SafeUrl]);
```

# Styling

The classes are `widget-title`, `widget-text`, `widget-image`, `widget-description` and `widget-button`, inside `.widget.widget-{type}`. The default styles are `resources/css/widgets.css`.

- **Blade:** require them where you render widgets:

  ```blade
  @manifestRequire('widgets::styles')
  ```

- **React and Inertia:** `<WidgetLocation>`, `<Widget>` and the built-in components import the stylesheet themselves, so Vite bundles it wherever they're used. Don't import it again in your app.

To change the styles everywhere, override the file with manifest (`php artisan manifest:override widgets css/widgets.css`): the override applies to the Blade asset and to the React imports. The button's colours come from `--widgets-button-background` and `--widgets-button-color`. To change the markup, publish the views (`--tag=widgets-views`), or register your own widget class with the same key.
