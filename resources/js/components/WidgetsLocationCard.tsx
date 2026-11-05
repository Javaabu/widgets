import { Button, Card, Form, Icon, LinkButton, Submit } from "@javaabu/forms"
import { useState, type MouseEvent } from "react"
import { format } from "../sortable"
import type { WidgetItemData, WidgetLocationData, WidgetsStrings } from "../types"
import { SortableList } from "./SortableList"

export interface WidgetsLocationCardProps {
  location: WidgetLocationData
  strings: WidgetsStrings
  instructionsId?: string
  /** Save a new order; the Index page sends it with the Inertia router */
  onReorder?: (location: WidgetLocationData, ids: number[]) => void
}

function confirmFirst(message: string) {
  return (event: MouseEvent<HTMLButtonElement>) => {
    if (!window.confirm(message)) event.preventDefault()
  }
}

/** A location on the admin page: its widgets in order, and a way to add one */
export function WidgetsLocationCard({ location, strings, instructionsId, onReorder }: WidgetsLocationCardProps) {
  const [type, setType] = useState(location.available[0]?.key ?? "")
  const selectId = `widgets-add-${location.key}`

  return (
    <div data-widgets-location={location.key}>
      <Card title={location.label}>
        <div className="flex flex-col gap-4">
          {location.description ? <p className="text-muted-foreground text-sm">{location.description}</p> : null}

          {location.widgets.length === 0 ? (
            <p className="text-muted-foreground text-sm" data-widgets-empty>
              {strings.empty}
            </p>
          ) : (
            <SortableList<WidgetItemData>
              items={location.widgets}
              getKey={(widget) => widget.id}
              getTitle={(widget) => widget.title}
              strings={strings}
              instructionsId={instructionsId}
              disabled={!location.can_manage}
              aria-label={location.label}
              className="flex flex-col gap-2"
              itemClassName="bg-background flex items-center gap-3 rounded-md border p-3 data-[dragging=true]:opacity-50 data-[sorting=true]:ring-2"
              onReorder={(widgets) =>
                onReorder?.(
                  location,
                  widgets.map((widget) => widget.id),
                )
              }
              renderItem={(widget, handle) => (
                <>
                  {location.can_manage ? (
                    <button
                      type="button"
                      {...handle}
                      className="text-muted-foreground hover:bg-muted flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md focus-visible:outline-2"
                      aria-label={format(strings.handle, { widget: widget.title })}
                    >
                      <Icon name="grip-vertical" />
                    </button>
                  ) : null}

                  <div className="flex min-w-0 flex-1 flex-col">
                    <span className="truncate font-medium" dir="auto">
                      {widget.title}
                    </span>
                    {widget.missing ? (
                      <span className="text-destructive text-sm">{strings.missing}</span>
                    ) : widget.title !== widget.name ? (
                      <span className="text-muted-foreground text-sm">{widget.name}</span>
                    ) : null}
                  </div>

                  {location.can_manage ? (
                    <div className="flex shrink-0 items-center gap-2">
                      {widget.missing ? null : (
                        <LinkButton url={widget.edit_url} color="outline-secondary" size="sm">
                          {strings.edit}
                        </LinkButton>
                      )}
                      <Form method="DELETE" action={widget.delete_url}>
                        <Submit color="danger" size="sm" animate={false} onClick={confirmFirst(strings.confirm_remove)}>
                          {strings.remove}
                        </Submit>
                      </Form>
                    </div>
                  ) : null}
                </>
              )}
            />
          )}

          {location.can_manage ? (
            location.is_full ? (
              <p className="text-muted-foreground text-sm">{strings.full}</p>
            ) : location.available.length > 0 ? (
              <form
                method="GET"
                action={location.create_url}
                className="flex flex-wrap items-end gap-2"
                data-widgets-add
              >
                <div className="flex min-w-48 flex-col gap-1">
                  <label htmlFor={selectId} className="text-sm font-medium">
                    {format(strings.add_to, { location: location.label })}
                  </label>
                  <select
                    id={selectId}
                    name="type"
                    value={type}
                    onChange={(event) => setType(event.target.value)}
                    className="bg-background h-9 rounded-md border px-3"
                    required
                  >
                    {location.available.map((widget) => (
                      <option key={widget.key} value={widget.key}>
                        {widget.name}
                      </option>
                    ))}
                  </select>
                </div>
                <Button type="submit" startIcon="plus">
                  {strings.add}
                </Button>
              </form>
            ) : null
          ) : null}
        </div>
      </Card>
    </div>
  )
}
