import type { WidgetComponentProps } from "../types"
import { filled } from "../url"

export type TextWidgetSettings = { title?: string | null; text?: string | null }

/** The built-in `text` widget: the same markup as widgets::widgets.text */
export function TextWidget({ settings }: WidgetComponentProps<TextWidgetSettings>) {
  return (
    <>
      {filled(settings.title) ? <h2 className="widget-title">{settings.title}</h2> : null}
      {filled(settings.text) ? <div className="widget-text">{settings.text}</div> : null}
    </>
  )
}
