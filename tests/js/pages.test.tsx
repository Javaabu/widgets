import { act, fireEvent, render, screen, within } from "@testing-library/react"
import { beforeEach, describe, expect, it, vi } from "vitest"
import { WidgetsEdit, WidgetsIndex, type WidgetsEditProps, type WidgetsIndexProps } from "@javaabu/widgets"
import editProps from "./fixtures/edit-props.json"
import indexProps from "./fixtures/index-props.json"

const put = vi.fn()

vi.mock("@inertiajs/react", () => ({
  router: { put: (...args: unknown[]) => put(...args) },
}))

beforeEach(() => put.mockReset())

describe("widgets::Widgets/Index", () => {
  it("lists every location with its widgets", () => {
    render(<WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />)

    expect(screen.getByRole("heading", { name: "Widgets" })).toBeInTheDocument()

    const sidebar = document.querySelector<HTMLElement>("[data-widgets-location=sidebar]")!
    expect(within(sidebar).getByText("މަރުޙަބާ")).toBeInTheDocument()
    expect(within(sidebar).getAllByRole("button", { name: /^Move / })).toHaveLength(2)
    expect(within(sidebar).getByLabelText("Add a widget to Sidebar")).toBeInTheDocument()

    // The footer has its own permission, which this user doesn't have
    const footer = document.querySelector<HTMLElement>("[data-widgets-location=footer]")!
    expect(within(footer).queryByRole("button", { name: /^Move / })).not.toBeInTheDocument()
    expect(within(footer).queryByText("Edit")).not.toBeInTheDocument()
  })

  it("saves a new order made with the keyboard", async () => {
    render(<WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />)

    const handle = screen.getByRole("button", { name: "Move މަރުޙަބާ" })
    expect(handle).toHaveAttribute("aria-describedby", "widgets-instructions")

    fireEvent.keyDown(handle, { key: " " })
    expect(handle).toHaveAttribute("aria-pressed", "true")

    await act(async () => {
      fireEvent.keyDown(handle, { key: "ArrowDown" })
    })

    expect(document.querySelector("[data-widgets-announcer]")).toHaveTextContent("މަރުޙަބާ moved to position 2 of 2.")

    const moved = screen.getByRole("button", { name: "Move މަރުޙަބާ" })
    fireEvent.keyDown(moved, { key: "Enter" })

    const sidebar = (indexProps as unknown as WidgetsIndexProps).locations[0]!
    expect(put).toHaveBeenCalledWith(
      sidebar.order_url,
      { widgets: [sidebar.widgets[1]!.id, sidebar.widgets[0]!.id] },
      expect.anything(),
    )
  })

  it("cancels a keyboard move with Escape", () => {
    render(<WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />)

    const handle = screen.getByRole("button", { name: "Move މަރުޙަބާ" })
    fireEvent.keyDown(handle, { key: " " })
    fireEvent.keyDown(handle, { key: "ArrowDown" })
    fireEvent.keyDown(screen.getByRole("button", { name: "Move މަރުޙަބާ" }), { key: "Escape" })

    const titles = Array.from(document.querySelectorAll("[data-widgets-location=sidebar] li")).map(
      (item) => item.textContent,
    )
    expect(titles[0]).toContain("މަރުޙަބާ")
    expect(put).not.toHaveBeenCalled()
  })

  it("reorders by dragging a handle", () => {
    render(<WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />)

    const items = document.querySelectorAll<HTMLLIElement>("[data-widgets-location=sidebar] li")
    const [first, second] = [items[0]!, items[1]!]
    first.getBoundingClientRect = () => ({ top: 100, height: 20 }) as DOMRect

    // Without the handle, the item doesn't drag
    fireEvent.dragStart(second)
    expect(second).not.toHaveAttribute("data-dragging")

    // jsdom's drag events have no clientY, so this drops second above first
    fireEvent.pointerDown(within(second).getByRole("button", { name: /^Move / }))
    fireEvent.dragStart(second)
    expect(second).toHaveAttribute("data-dragging", "true")
    fireEvent.dragOver(first)
    fireEvent.dragEnd(second)

    const sidebar = (indexProps as unknown as WidgetsIndexProps).locations[0]!
    expect(put).toHaveBeenCalledWith(
      sidebar.order_url,
      { widgets: [sidebar.widgets[1]!.id, sidebar.widgets[0]!.id] },
      expect.anything(),
    )
  })

  it("asks before removing a widget", () => {
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false)
    render(<WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />)

    fireEvent.click(screen.getAllByRole("button", { name: "Remove" })[0]!)

    expect(confirm).toHaveBeenCalledWith("Remove this widget?")
    confirm.mockRestore()
  })
})

describe("widgets::Widgets/Edit", () => {
  it("renders the widget's settings form", () => {
    render(<WidgetsEdit {...(editProps as unknown as WidgetsEditProps)} />)

    expect(screen.getByRole("heading", { name: "Edit Link in Sidebar" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "Back to widgets" })).toHaveAttribute("href", "http://localhost/widgets")
    expect(screen.getByLabelText(/^Title/)).toHaveValue("ގުޅުއްވާ")
    expect(screen.getByLabelText(/^Location/)).toBeInTheDocument()
    expect(screen.getByRole("button", { name: "Save widget" })).toBeInTheDocument()
  })
})
