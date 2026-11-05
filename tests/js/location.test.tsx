import { render, screen } from "@testing-library/react"
import { describe, expect, it, vi } from "vitest"
import {
  ImageWidget,
  LinkWidget,
  registerWidgets,
  registeredWidgets,
  resolveWidget,
  Widget,
  WidgetLocation,
  type WidgetComponentProps,
  type WidgetData,
} from "@javaabu/widgets"

const widget = (overrides: Partial<WidgetData> = {}): WidgetData => ({
  id: 1,
  type: "text",
  component: "Text",
  location: "sidebar",
  settings: { title: "މަރުޙަބާ", text: "ލިޔުން" },
  data: {},
  ...overrides,
})

function Counter({ settings, data }: WidgetComponentProps<{ label: string }, { doubled: number }>) {
  return (
    <p>
      {settings.label}: {data.doubled}
    </p>
  )
}

describe("WidgetLocation", () => {
  it("renders the widgets in order", () => {
    render(
      <WidgetLocation
        name="sidebar"
        widgets={[widget(), widget({ id: 2, settings: { text: "ދެވަނަ" } })]}
        className="gap-4"
      />,
    )

    const location = document.querySelector("[data-widget-location=sidebar]")!
    expect(location).toHaveClass("widgets", "widgets-sidebar", "gap-4")
    expect(location.querySelectorAll("section")).toHaveLength(2)
    expect(screen.getByRole("heading", { name: "މަރުޙަބާ" })).toBeInTheDocument()
    expect(document.querySelector("#widget-2")).toHaveTextContent("ދެވަނަ")
  })

  it("renders nothing without widgets, unless always", () => {
    const { container, rerender } = render(<WidgetLocation name="sidebar" widgets={[]} />)
    expect(container).toBeEmptyDOMElement()

    rerender(<WidgetLocation name="sidebar" widgets={null} always />)
    expect(container.querySelector("[data-widget-location]")).toBeInTheDocument()
  })

  it("uses components given by type or by component name", () => {
    render(
      <WidgetLocation
        name="sidebar"
        widgets={[widget({ type: "counter", component: "Counter", settings: { label: "ގިނަ" }, data: { doubled: 8 } })]}
        components={{ Counter }}
      />,
    )

    expect(screen.getByText("ގިނަ: 8")).toBeInTheDocument()
  })

  it("registers components for every location", () => {
    registerWidgets({ counter: Counter })

    expect(registeredWidgets()).toHaveProperty("counter")
    expect(resolveWidget(widget({ type: "counter", component: null }))).toBe(Counter)
    expect(resolveWidget(widget({ type: "unknown", component: "Unknown" }))).toBeNull()
  })

  it("skips widgets without a component and warns in development", () => {
    const warn = vi.spyOn(console, "warn").mockImplementation(() => {})
    const { container } = render(<Widget widget={widget({ type: "nope", component: "Nope" })} />)

    expect(container).toBeEmptyDOMElement()
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('"nope"'))
    warn.mockRestore()
  })

  it("takes a custom wrapper", () => {
    render(
      <Widget widget={widget()} wrapper={({ widget, children }) => <aside data-id={widget.id}>{children}</aside>} />,
    )

    expect(document.querySelector("aside[data-id='1'] .widget-text")).toHaveTextContent("ލިޔުން")
  })

  it("never renders unsafe links", () => {
    const { container } = render(
      <>
        <LinkWidget widget={widget()} settings={{ label: "Go", url: "javascript:alert(1)" }} data={{}} />
        <ImageWidget widget={widget()} settings={{ src: "javascript:alert(1)" }} data={{}} />
        <ImageWidget widget={widget()} settings={{ src: "/a.png", link: "javascript:alert(1)" }} data={{}} />
      </>,
    )

    expect(container.querySelectorAll("a")).toHaveLength(0)
    expect(container.querySelector("img")).toHaveAttribute("src", "/a.png")
  })
})
