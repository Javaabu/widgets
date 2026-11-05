---
title: Translatable settings
sidebar_position: 2
---

On a bilingual site, a widget's title and text are different in English and Dhivehi. Mark those settings `->translatable()`:

```php
public function settings(): array
{
    return [
        TextInput::make('title')->label(__('Title'))->translatable(),
        Textarea::make('text')->label(__('Text'))->required()->translatable(),
        TextInput::make('url')->label(__('Link'))->rules([new SafeUrl]),   // the same in every language
    ];
}
```

The built-in widgets already mark their titles, text, alternative text, captions and button text.

# With javaabu/translatable

When [javaabu/translatable](https://github.com/Javaabu/translatable) is installed and has active languages, it works like this:

- **The form** has one input per language for each translatable setting, named `{setting}_{locale}`: `title_en`, `title_dv`. The Dhivehi input has the Thaana keyboard. Both the Blade and the React forms do this, through stencil.
- **Validation:** the primary language (the current locale) keeps the field's rules. The other languages are optional.
- **Storage:** the setting is stored as a map, `"title": { "en": "Hello", "dv": "މަރުޙަބާ" }`. Saving keeps languages the form didn't send.
- **Rendering:** `$widget->setting('title')`, the Blade view, `Widgets::toArray()` and React all get the current locale's value. When it's empty, they fall back to the default locale, then to any language that has a value.

```php
$widget->setting('title');            // 'މަރުޙަބާ' when the locale is dv
$widget->translations('title');       // ['en' => 'Hello', 'dv' => 'މަރުޙަބާ']
$widget->localizedSettings('en');     // every setting in English
```

The form implements stencil's `ResolvesTranslatableFields` contract, so the widget model doesn't need translatable's trait.

# Without it

javaabu/translatable is optional: it's in `suggest`, and the package only uses it after checking that it's installed (`Javaabu\Widgets\Support\Translations`). Without it, or with `translatable.enabled` off, a translatable setting is a plain field holding one value.

- **Installing translatable later:** values saved before you installed it are read as the default locale's value.
- **Removing it:** settings saved as maps still render, in the current locale or the default one.

Set `Javaabu\Widgets\Support\Translations::$enabled = false` to turn translatable settings off even when the package is installed.
