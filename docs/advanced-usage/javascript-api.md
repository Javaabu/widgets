---
title: JavaScript API
sidebar_position: 5
---

The package's TypeScript lives in `resources/js` and reaches your app as `@javaabu/widgets` through [javaabu/manifest](https://github.com/Javaabu/manifest). It isn't on npm. The Blade admin script is also prebuilt in `dist/` for apps without Vite.

# Rendering widgets

```tsx
import { WidgetLocation, Widget, registerWidgets, resolveWidget, builtInWidgets } from "@javaabu/widgets"
```

| Export | |
| --- | --- |
| `<WidgetLocation name widgets components? wrapper? always? …divProps>` | A location's widgets, in the same markup as `<x-widgets::location>` |
| `<Widget widget components? wrapper?>` | One widget in its `<section>` wrapper |
| `registerWidgets(components)` | Register components by widget key or component name, for every location |
| `registeredWidgets()` | The registered components, the built-ins included |
| `resolveWidget(widget, components?)` | The component for a widget, or null |
| `builtInWidgets` | `{ text: TextWidget, image: ImageWidget, link: LinkWidget }` |
| `TextWidget`, `ImageWidget`, `LinkWidget` | The built-in components |
| `isSafeUrl(url)`, `safeUrl(url)`, `isExternalUrl(url)`, `filled(value)` | Helpers the built-ins use |

Types: `WidgetData<Settings, Data>`, `WidgetLocations`, `WidgetComponentProps<Settings, Data>`, `WidgetComponent`, `WidgetComponents`.

`WidgetLocation`, `Widget` and the built-in components import `resources/css/widgets.css`, so Vite bundles the widget styles with them. Your own widget components bring their own styles.

# Admin pages

| Export | |
| --- | --- |
| `WidgetsIndex` | The `widgets::Widgets/Index` page |
| `WidgetsEdit` | The `widgets::Widgets/Edit` page |
| `WidgetsLocationCard` | One location on the index page: the sortable list, edit and remove buttons, and the add form. `onReorder(location, ids)` saves the order. |
| `SortableList<T>` | A list sorted by drag and drop or the keyboard, with a live region |

Types: `WidgetsIndexProps`, `WidgetsEditProps`, `WidgetLocationData`, `WidgetItemData`, `WidgetTypeData`, `WidgetsStrings`.

## SortableList

```tsx
import { SortableList } from "@javaabu/widgets"

<SortableList
  items={items}
  getKey={(item) => item.id}
  getTitle={(item) => item.title}             // what screen readers call it
  strings={strings}                           // picked_up, moved, dropped, cancelled (":widget", ":position", ":total")
  instructionsId="widgets-instructions"       // the element that explains the keys
  onReorder={(items) => save(items.map((item) => item.id))}
  renderItem={(item, handle) => (
    <>
      <button type="button" {...handle} aria-label={`Move ${item.title}`}>⠿</button>
      {item.title}
    </>
  )}
/>
```

Spread `handle` on the drag handle. Only the handle starts a drag, so the rest of the item stays clickable. The keys are Space or Enter to pick up and drop, the arrow keys, Home and End to move, and Escape to cancel. `disabled` turns sorting off. New `items` from the server replace the local order.

# Without React

```ts
import { createSortable, moveItem, announcement, keyboardTarget, format } from "@javaabu/widgets"

const sortable = createSortable(document.querySelector("ol")!, {
  itemSelector: "[data-widget-id]",          // the default
  handleSelector: "[data-widgets-handle]",   // the default
  announce: (message) => (liveRegion.textContent = message),
  onChange: (items) => save(items.map((item) => item.dataset.widgetId)),
})

sortable.destroy()
```

`createSortable()` moves the DOM elements themselves, so form inputs inside the items follow them. The Blade admin script (`widgets::admin`, `resources/js/blade/admin.ts`) uses it on every `[data-widgets-sortable]` list. It sends the surrounding `form[data-widgets-order]` with `fetch` after each drop, and fires a `widgets:order` event on `[data-widgets-admin]` with `{ saved, location }`.
