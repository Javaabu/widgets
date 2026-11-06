// The widget styles; Vite bundles them wherever the component is used (Blade loads widgets::styles)
import "../../css/widgets.css"
import type { WidgetComponentProps } from "../types"
import { filled, safeUrl } from "../url"

export type ImageWidgetSettings = {
  title?: string | null
  src?: string | null
  alt?: string | null
  caption?: string | null
  link?: string | null
}

export type ImageWidgetData = { src?: string | null; link?: string | null }

/** The built-in `image` widget: the same markup as widgets::widgets.image */
export function ImageWidget({ settings, data }: WidgetComponentProps<ImageWidgetSettings, ImageWidgetData>) {
  const src = safeUrl(data.src ?? settings.src)
  const link = safeUrl(data.link ?? settings.link)

  if (!src) return null

  const image = <img src={src} alt={settings.alt ?? ""} loading="lazy" />

  return (
    <>
      {filled(settings.title) ? <h2 className="widget-title">{settings.title}</h2> : null}
      <figure className="widget-image">
        {link ? <a href={link}>{image}</a> : image}
        {filled(settings.caption) ? <figcaption>{settings.caption}</figcaption> : null}
      </figure>
    </>
  )
}
