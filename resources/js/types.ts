import type { StencilSchema } from "@javaabu/stencil"
import type { ComponentType } from "react"

/** One widget in a location, as `Widgets::toArray()` / `Widgets::props()` serialize it */
export interface WidgetData<
  Settings extends Record<string, unknown> = Record<string, unknown>,
  Data extends Record<string, unknown> = Record<string, unknown>,
> {
  id: number
  /** The widget's key: `text`, `recent-posts` */
  type: string
  /** The component name from `Widget::component()`: `Text`, `RecentPosts` */
  component: string | null
  location: string
  /** The settings in the current locale, with defaults filled in */
  settings: Settings
  /** What `Widget::data()` returned */
  data: Data
}

/** Locations keyed by location, from `Widgets::props()` */
export type WidgetLocations = Record<string, WidgetData[]>

export interface WidgetComponentProps<
  Settings extends Record<string, unknown> = Record<string, unknown>,
  Data extends Record<string, unknown> = Record<string, unknown>,
> {
  widget: WidgetData<Settings, Data>
  settings: Settings
  data: Data
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type WidgetComponent = ComponentType<WidgetComponentProps<any, any>>

/** Components keyed by widget type (`recent-posts`) or component name (`RecentPosts`) */
export type WidgetComponents = Record<string, WidgetComponent>

/* Admin pages */

export interface WidgetTypeData {
  key: string
  name: string
  description: string | null
  icon: string | null
  component: string
  create_url?: string
}

export interface WidgetItemData {
  id: number
  type: string
  location: string
  title: string
  name: string
  icon: string | null
  missing: boolean
  order: number
  edit_url: string
  delete_url: string
}

export interface WidgetLocationData {
  key: string
  label: string
  description: string | null
  max: number | null
  can_manage: boolean
  is_full: boolean
  order_url: string
  create_url: string
  widgets: WidgetItemData[]
  available: WidgetTypeData[]
}

export interface WidgetsStrings {
  title: string
  description: string
  empty: string
  no_locations: string
  add: string
  add_to: string
  choose: string
  edit: string
  remove: string
  confirm_remove: string
  full: string
  missing: string
  back: string
  cancel: string
  new_title: string
  edit_title: string
  handle: string
  save_order: string
  order_saved: string
  order_failed: string
  instructions: string
  picked_up: string
  moved: string
  dropped: string
  cancelled: string
}

/** widgets::Widgets/Index */
export interface WidgetsIndexProps {
  locations: WidgetLocationData[]
  status: string | null
  strings: WidgetsStrings
}

/** widgets::Widgets/Edit, for a new widget (instance null) or an existing one */
export interface WidgetsEditProps {
  form: StencilSchema
  location: { key: string; label: string; description: string | null; max: number | null }
  widget: WidgetTypeData
  instance: WidgetItemData | null
  index_url: string
  strings: WidgetsStrings
}
