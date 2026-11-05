import { renderToStaticMarkup } from "react-dom/server"
import { describe, expect, it } from "vitest"
import { WidgetLocation, type WidgetData } from "@javaabu/widgets"
import image from "../Fixtures/rendering/image.json"
import link from "../Fixtures/rendering/link.json"
import text from "../Fixtures/rendering/text.json"

/** Parses HTML and drops whitespace-only text between tags, so Blade's newlines don't count */
function normalize(html: string): string {
  const template = document.createElement("template")
  template.innerHTML = html.replace(/\/>/g, ">")

  const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_TEXT)
  const empty: Text[] = []
  while (walker.nextNode()) {
    const node = walker.currentNode as Text
    if (node.data.trim() === "") empty.push(node)
  }
  empty.forEach((node) => node.remove())

  return template.innerHTML
}

describe("rendering fixtures (tests/Fixtures/rendering, written by RenderingFixturesTest)", () => {
  it.each([
    ["text", text],
    ["image", image],
    ["link", link],
  ])("renders %s like Blade", (_name, fixture) => {
    const html = renderToStaticMarkup(<WidgetLocation name="sidebar" widgets={fixture.widgets as WidgetData[]} />)

    expect(normalize(html)).toBe(normalize(fixture.html))
  })
})
