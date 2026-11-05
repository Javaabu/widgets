import { createElement, type HTMLAttributes, type ReactNode } from "react"
import { resolveWidget } from "../registry"
import type { WidgetComponents, WidgetData } from "../types"

export interface WidgetProps {
  widget: WidgetData
  /** Components for widget types, over the registered ones */
  components?: WidgetComponents
  /** Wrap the widget yourself; the default is the same <section> as widgets::wrapper */
  wrapper?: (props: { widget: WidgetData; children: ReactNode }) => ReactNode
}

/** One widget in its wrapper; nothing when no component is registered for its type */
export function Widget({ widget, components, wrapper }: WidgetProps) {
  const Component = resolveWidget(widget, components)

  if (!Component) {
    if (import.meta.env?.DEV) {
      console.warn(
        `[@javaabu/widgets] No component for the widget type "${widget.type}". Register one with registerWidgets().`,
      )
    }
    return null
  }

  // A registered component, looked up rather than created during render
  const content = createElement(Component, { widget, settings: widget.settings, data: widget.data ?? {} })

  if (wrapper) return <>{wrapper({ widget, children: content })}</>

  return (
    <section className={`widget widget-${widget.type}`} id={`widget-${widget.id}`} data-widget={widget.type}>
      {content}
    </section>
  )
}

export interface WidgetLocationProps extends Omit<HTMLAttributes<HTMLDivElement>, "children"> {
  /** The location's key, for the class and data attribute */
  name: string
  /** The location's widgets: `Widgets::toArray('sidebar')` or `Widgets::props()['sidebar']` */
  widgets: WidgetData[] | null | undefined
  components?: WidgetComponents
  wrapper?: WidgetProps["wrapper"]
  /** Render the container even when there are no widgets */
  always?: boolean
}

/** A location's widgets, in order: the same markup as <x-widgets::location> */
export function WidgetLocation({
  name,
  widgets,
  components,
  wrapper,
  always = false,
  className,
  ...props
}: WidgetLocationProps) {
  const list = widgets ?? []

  if (list.length === 0 && !always) return null

  return (
    <div
      {...props}
      className={["widgets", `widgets-${name}`, className].filter(Boolean).join(" ")}
      data-widget-location={name}
    >
      {list.map((widget) => (
        <Widget key={widget.id} widget={widget} components={components} wrapper={wrapper} />
      ))}
    </div>
  )
}
