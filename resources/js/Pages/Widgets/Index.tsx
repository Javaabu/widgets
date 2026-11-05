import { Alert } from "@javaabu/forms"
import { router } from "@inertiajs/react"
import { WidgetsLocationCard } from "../../components/WidgetsLocationCard"
import type { WidgetLocationData, WidgetsIndexProps } from "../../types"

/** widgets::Widgets/Index — every location with its widgets */
export default function Index({ locations, status, strings }: WidgetsIndexProps) {
  const reorder = (location: WidgetLocationData, ids: number[]) => {
    router.put(location.order_url, { widgets: ids }, { preserveScroll: true, preserveState: true })
  }

  return (
    <div className="flex flex-col gap-6" data-widgets-admin>
      <div className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold">{strings.title}</h1>
        <p className="text-muted-foreground">{strings.description}</p>
      </div>

      {status ? <Alert type="success">{status}</Alert> : null}

      <p id="widgets-instructions" className="sr-only">
        {strings.instructions}
      </p>

      {locations.length === 0 ? <Alert type="info">{strings.no_locations}</Alert> : null}

      {locations.map((location) => (
        <WidgetsLocationCard
          key={location.key}
          location={location}
          strings={strings}
          instructionsId="widgets-instructions"
          onReorder={reorder}
        />
      ))}
    </div>
  )
}
