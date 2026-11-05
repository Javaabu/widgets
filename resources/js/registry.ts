import type { WidgetComponent, WidgetComponents, WidgetData } from "./types"
import { ImageWidget } from "./widgets/ImageWidget"
import { LinkWidget } from "./widgets/LinkWidget"
import { TextWidget } from "./widgets/TextWidget"

/** The components of the built-in widgets, by type */
export const builtInWidgets: WidgetComponents = {
  text: TextWidget,
  image: ImageWidget,
  link: LinkWidget,
}

const registered: WidgetComponents = { ...builtInWidgets }

/**
 * Register components for your widget types, once, where your app starts:
 *
 * ```ts
 * registerWidgets({ "recent-posts": RecentPosts })
 * ```
 *
 * Keys are widget types (`recent-posts`) or component names (`RecentPosts`).
 */
export function registerWidgets(components: WidgetComponents): void {
  Object.assign(registered, components)
}

/** The registered components, the built-ins included */
export function registeredWidgets(): WidgetComponents {
  return { ...registered }
}

/**
 * The component for a widget: by type, then by component name, in the
 * components given, then in the registered ones
 */
export function resolveWidget(widget: WidgetData, components: WidgetComponents = {}): WidgetComponent | null {
  const keys = [widget.type, widget.component].filter((key): key is string => !!key)

  for (const source of [components, registered]) {
    for (const key of keys) {
      const component = source[key]
      if (component) return component
    }
  }

  return null
}
