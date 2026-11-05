/**
 * The Blade admin page (widgets::pages.index): drag and drop and keyboard
 * ordering that saves on drop, and confirmation before removing a widget.
 * Required by the page with @manifestRequire('widgets::admin').
 */
import { createSortable, type SortableStrings } from "../sortable"

type AdminStrings = Partial<SortableStrings> & { order_saved?: string; order_failed?: string; confirm_remove?: string }

function readStrings(root: HTMLElement): AdminStrings {
  try {
    return JSON.parse(root.dataset.strings ?? "{}") as AdminStrings
  } catch {
    return {}
  }
}

async function saveOrder(form: HTMLFormElement): Promise<boolean> {
  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    })

    return response.ok
  } catch {
    return false
  }
}

export function initWidgetsAdmin(root: HTMLElement): () => void {
  const strings = readStrings(root)
  const announcer = root.querySelector<HTMLElement>("[data-widgets-announcer]")
  const announce = (message: string) => {
    if (!announcer) return
    // Clearing first makes screen readers repeat the same message
    announcer.textContent = ""
    window.setTimeout(() => (announcer.textContent = message), 50)
  }

  const cleanups: Array<() => void> = []

  for (const list of root.querySelectorAll<HTMLElement>("[data-widgets-sortable]")) {
    const form = list.closest<HTMLFormElement>("form[data-widgets-order]")

    const sortable = createSortable(list, {
      strings,
      announce,
      onChange: async () => {
        if (!form) return
        const saved = await saveOrder(form)
        const message = saved ? strings.order_saved : strings.order_failed
        if (message) window.setTimeout(() => announce(message), 400)
        root.dispatchEvent(
          new CustomEvent("widgets:order", {
            detail: { saved, location: list.closest<HTMLElement>("[data-widgets-location]")?.dataset.widgetsLocation },
          }),
        )
      },
    })

    cleanups.push(() => sortable.destroy())
  }

  const onSubmit = (event: SubmitEvent) => {
    const form = event.target instanceof HTMLFormElement ? event.target : null
    const message = form?.dataset.widgetsConfirm
    if (message && !window.confirm(message)) event.preventDefault()
  }

  // Remove buttons submit hidden forms outside the list (form="…"), so listen on the document
  document.addEventListener("submit", onSubmit, true)
  cleanups.push(() => document.removeEventListener("submit", onSubmit, true))

  return () => cleanups.forEach((cleanup) => cleanup())
}

function boot() {
  for (const root of document.querySelectorAll<HTMLElement>("[data-widgets-admin]")) {
    if (root.dataset.widgetsReady) continue
    root.dataset.widgetsReady = "true"
    initWidgetsAdmin(root)
  }
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot)
  else boot()
}
