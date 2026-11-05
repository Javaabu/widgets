import { afterEach, describe, expect, it, vi } from "vitest"
import { initWidgetsAdmin } from "../../resources/js/blade/admin"

function page() {
  document.body.innerHTML = `
    <div data-widgets-admin data-strings='{"order_saved":"ތަރުތީބު ރައްކާކުރެވިއްޖެ","order_failed":"Failed","picked_up":":widget ނެގީ"}'>
      <div class="sr-only" aria-live="assertive" data-widgets-announcer></div>
      <div data-widgets-location="sidebar">
        <form method="POST" action="/widgets/sidebar/order" data-widgets-order>
          <input type="hidden" name="_method" value="PUT">
          <ol data-widgets-sortable>
            <li data-widget-id="1" data-widget-title="A"><input type="hidden" name="widgets[]" value="1"><button type="button" data-widgets-handle>≡</button></li>
            <li data-widget-id="2" data-widget-title="B"><input type="hidden" name="widgets[]" value="2"><button type="button" data-widgets-handle>≡</button></li>
          </ol>
        </form>
        <form method="POST" action="/widgets/sidebar/1" id="remove-1" data-widgets-confirm="Remove this widget?"></form>
      </div>
    </div>`

  return document.querySelector<HTMLElement>("[data-widgets-admin]")!
}

const key = (target: Element, key: string) =>
  target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }))

afterEach(() => {
  vi.useRealTimers()
  vi.unstubAllGlobals()
})

describe("the Blade admin script", () => {
  it("saves the order in the DOM's order after a keyboard move", async () => {
    vi.useFakeTimers()
    const fetch = vi.fn().mockResolvedValue({ ok: true })
    vi.stubGlobal("fetch", fetch)

    const root = page()
    const saved = vi.fn()
    root.addEventListener("widgets:order", (event) => saved((event as CustomEvent).detail))
    initWidgetsAdmin(root)

    const handle = root.querySelector("[data-widgets-handle]")!
    key(handle, " ")
    await vi.advanceTimersByTimeAsync(60)
    expect(root.querySelector("[data-widgets-announcer]")).toHaveTextContent("A ނެގީ")

    key(handle, "ArrowDown")
    key(handle, " ")
    await vi.advanceTimersByTimeAsync(500)

    expect(fetch).toHaveBeenCalledTimes(1)
    const [url, init] = fetch.mock.calls[0]!
    expect(url).toContain("/widgets/sidebar/order")
    expect((init.body as FormData).getAll("widgets[]")).toEqual(["2", "1"])
    expect((init.body as FormData).get("_method")).toBe("PUT")
    expect(init.headers.Accept).toBe("application/json")
    expect(saved).toHaveBeenCalledWith({ saved: true, location: "sidebar" })
    expect(root.querySelector("[data-widgets-announcer]")).toHaveTextContent("ތަރުތީބު ރައްކާކުރެވިއްޖެ")
  })

  it("announces when the order can't be saved", async () => {
    vi.useFakeTimers()
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("offline")))

    const root = page()
    initWidgetsAdmin(root)

    const handle = root.querySelector("[data-widgets-handle]")!
    key(handle, " ")
    key(handle, "End")
    key(handle, " ")
    await vi.advanceTimersByTimeAsync(500)

    expect(root.querySelector("[data-widgets-announcer]")).toHaveTextContent("Failed")
  })

  it("asks before removing a widget", () => {
    const root = page()
    const cleanup = initWidgetsAdmin(root)
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false)

    const form = document.getElementById("remove-1") as HTMLFormElement
    const event = new SubmitEvent("submit", { bubbles: true, cancelable: true })
    form.dispatchEvent(event)

    expect(confirm).toHaveBeenCalledWith("Remove this widget?")
    expect(event.defaultPrevented).toBe(true)

    cleanup()
    confirm.mockRestore()
  })
})
