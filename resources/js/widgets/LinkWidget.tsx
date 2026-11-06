// The widget styles; Vite bundles them wherever the component is used (Blade loads widgets::styles)
import "../../css/widgets.css"
import type { WidgetComponentProps } from "../types"
import { filled, isExternalUrl, safeUrl } from "../url"

export type LinkWidgetSettings = {
  title?: string | null
  description?: string | null
  label?: string | null
  url?: string | null
  new_tab?: boolean | null
}

export type LinkWidgetData = { url?: string | null; external?: boolean }

/** The built-in `link` widget: the same markup as widgets::widgets.link */
export function LinkWidget({ settings, data }: WidgetComponentProps<LinkWidgetSettings, LinkWidgetData>) {
  const url = safeUrl(data.url ?? settings.url)

  if (!url || !filled(settings.label)) return null

  const external = data.external ?? isExternalUrl(url)
  const rel = settings.new_tab ? "noopener noreferrer" : external ? "noopener" : undefined

  return (
    <>
      {filled(settings.title) ? <h2 className="widget-title">{settings.title}</h2> : null}
      {filled(settings.description) ? <p className="widget-description">{settings.description}</p> : null}
      <a className="widget-button" href={url} target={settings.new_tab ? "_blank" : undefined} rel={rel}>
        {settings.label}
      </a>
    </>
  )
}
