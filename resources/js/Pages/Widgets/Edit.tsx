import { StencilForm } from "@javaabu/stencil"
import { format } from "../../sortable"
import type { WidgetsEditProps } from "../../types"

/** widgets::Widgets/Edit — the settings form of a new or an existing widget */
export default function Edit({ form, location, widget, instance, index_url, strings }: WidgetsEditProps) {
  const title = format(instance ? strings.edit_title : strings.new_title, {
    widget: widget.name,
    location: location.label,
  })

  return (
    <div className="flex flex-col gap-6" data-widget-edit={widget.key}>
      <div className="flex flex-col gap-1">
        <a href={index_url} className="text-muted-foreground text-sm hover:underline">
          {strings.back}
        </a>
        <h1 className="text-2xl font-semibold">{title}</h1>
        {widget.description ? <p className="text-muted-foreground">{widget.description}</p> : null}
      </div>

      <StencilForm key={instance?.id ?? `new-${widget.key}`} schema={form} />
    </div>
  )
}
