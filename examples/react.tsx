import { StrictMode, useState } from "react"
import { createRoot } from "react-dom/client"
import { FormsProvider } from "@javaabu/forms"
import { html } from "@javaabu/forms/html"
import {
  registerWidgets,
  WidgetLocation,
  WidgetsIndex,
  type WidgetComponentProps,
  type WidgetData,
  type WidgetsIndexProps,
} from "@javaabu/widgets"
import indexProps from "../tests/js/fixtures/index-props.json"
import image from "../tests/Fixtures/rendering/image.json"
import link from "../tests/Fixtures/rendering/link.json"
import text from "../tests/Fixtures/rendering/text.json"

/** A widget type of the app's own, as it would register it */
function Counter({ settings, data }: WidgetComponentProps<{ label: string }, { doubled: number }>) {
  return (
    <p>
      {settings.label}: {data.doubled}
    </p>
  )
}

registerWidgets({ counter: Counter })

const sidebar = [
  ...(text.widgets as WidgetData[]),
  ...(image.widgets as WidgetData[]),
  ...(link.widgets as WidgetData[]),
  {
    id: 99,
    type: "counter",
    component: "Counter",
    location: "sidebar",
    settings: { label: "ގިނަ" },
    data: { doubled: 8 },
  },
].map((widget, index) => ({ ...widget, id: index + 1 }))

function App() {
  const [rtl, setRtl] = useState(false)

  return (
    <main dir={rtl ? "rtl" : "ltr"} lang={rtl ? "dv" : "en"}>
      <header>
        <h1>React</h1>
        <p>
          <a href="./index.html">Blade example</a> ·{" "}
          <button type="button" onClick={() => setRtl(!rtl)}>
            RTL / ދިވެހި
          </button>
        </p>
      </header>

      <div className="columns">
        <section>
          <h2>Admin page (widgets::Widgets/Index)</h2>
          <WidgetsIndex {...(indexProps as unknown as WidgetsIndexProps)} />
        </section>

        <aside>
          <h2>&lt;WidgetLocation name="sidebar" /&gt;</h2>
          <WidgetLocation name="sidebar" widgets={sidebar} />
        </aside>
      </div>
    </main>
  )
}

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <FormsProvider ui={html}>
      <App />
    </FormsProvider>
  </StrictMode>,
)
