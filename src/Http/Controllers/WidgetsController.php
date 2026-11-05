<?php

namespace Javaabu\Widgets\Http\Controllers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Javaabu\Widgets\Forms\WidgetForm;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\Routing\WidgetRoutes;
use Javaabu\Widgets\Views\ViewRenderer;
use Javaabu\Widgets\Widget;
use Javaabu\Widgets\WidgetLocation;
use Javaabu\Widgets\WidgetsManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The admin pages: every location with its widgets, and a settings form per
 * widget. Extend it and set `config('widgets.controllers.widgets')` (or pass
 * `controller` to Route::widgets()) to change what it does.
 */
class WidgetsController extends Controller
{
    public function __construct(
        protected WidgetsManager $widgets,
        protected ViewRenderer $views,
    ) {}

    /**
     * Every location the user can see, with its widgets and the widgets that can be added
     */
    public function index(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeFor($request, 'viewAny', WidgetLocation::class);

        $locations = $this->locationsData($request);

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $locations]);
        }

        return $this->views->render('widgets.index', [
            'locations' => $locations,
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'strings' => $this->strings(),
            'layout' => config('widgets.layout', 'layouts.app'),
            'layout_section' => config('widgets.layout_section', 'content'),
        ]);
    }

    public function create(Request $request, string $widget_location): View|Responsable|JsonResponse
    {
        $location = $this->findLocation($widget_location);

        $this->authorizeFor($request, 'create', $location);

        $widget = $this->findType($location, (string) $request->query('type'));

        $form = $this->form($request, $widget, $location)
            ->action(route($this->routeName($request, 'store'), [$location->getKey(), 'type' => $widget::key()]))
            ->method('POST');

        if ($request->expectsJson()) {
            return new JsonResponse(['widget' => $widget->toArray(), 'form' => $form->render('json')]);
        }

        return $this->views->render('widgets.edit', $this->editData($request, $location, $widget, null, $form));
    }

    public function store(Request $request, string $widget_location): RedirectResponse|JsonResponse
    {
        $location = $this->findLocation($widget_location);

        $this->authorizeFor($request, 'create', $location);

        $widget = $this->findType($location, (string) ($request->query('type') ?? $request->input('type')));

        $this->ensureRoomIn($location, 'type');

        $instance = $this->form($request, $widget, $location)->saveWidget($request);

        $this->saved($request, $instance);

        return $this->savedResponse($request, $instance, __('Widget added.'), 201);
    }

    public function edit(Request $request, string $widget_location, string $widget): View|Responsable|JsonResponse
    {
        $location = $this->findLocation($widget_location);
        $instance = $this->findWidget($location, $widget);

        $this->authorizeFor($request, 'update', $instance);

        $definition = $this->definitionOf($instance);

        $form = $this->form($request, $instance, $location)
            ->action(route($this->routeName($request, 'update'), [$location->getKey(), $instance->getKey()]))
            ->method('PATCH');

        if ($request->expectsJson()) {
            return new JsonResponse([
                'widget' => $this->widgetData($request, $location, $instance),
                'settings' => $instance->settings ?? [],
            ]);
        }

        return $this->views->render('widgets.edit', $this->editData($request, $location, $definition, $instance, $form));
    }

    public function update(Request $request, string $widget_location, string $widget): RedirectResponse|JsonResponse
    {
        $location = $this->findLocation($widget_location);
        $instance = $this->findWidget($location, $widget);

        $this->authorizeFor($request, 'update', $instance);

        $this->definitionOf($instance);

        $target = $request->input(WidgetForm::LOCATION);

        if (is_string($target) && $target !== $location->getKey()) {
            $this->ensureCanMoveTo($request, $instance, $target);
        }

        $instance = $this->form($request, $instance, $location)->saveWidget($request);

        $this->saved($request, $instance);

        return $this->savedResponse($request, $instance, __('Widget saved.'));
    }

    public function destroy(Request $request, string $widget_location, string $widget): RedirectResponse|JsonResponse|Response
    {
        $location = $this->findLocation($widget_location);
        $instance = $this->findWidget($location, $widget);

        $this->authorizeFor($request, 'delete', $instance);

        $instance->delete();

        $this->deleted($request, $instance);

        $message = __('Widget removed.');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message]);
        }

        return redirect()
            ->route($this->routeName($request, 'index'))
            ->with('status', $message);
    }

    /**
     * Put the location's widgets in the order of the ids in `widgets`
     */
    public function order(Request $request, string $widget_location): RedirectResponse|JsonResponse
    {
        $location = $this->findLocation($widget_location);

        $this->authorizeFor($request, 'reorder', $location);

        $validated = $request->validate([
            'widgets' => ['required', 'array'],
            'widgets.*' => ['integer', 'distinct'],
        ], [], [
            'widgets' => __('widgets'),
        ]);

        $ids = array_map('intval', $validated['widgets']);
        $widgets = $this->widgets->model()::query()->inLocation($location)->get()->keyBy(fn (WidgetInstance $widget) => (int) $widget->getKey());

        $unknown = array_diff($ids, $widgets->keys()->all());

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'widgets' => __('Some of these widgets aren\'t in :location.', ['location' => $location->getLabel()]),
            ]);
        }

        // Widgets left out keep their place after the ones given
        $ordered = [...$ids, ...array_values(array_diff($widgets->keys()->all(), $ids))];

        foreach ($ordered as $position => $id) {
            $widget = $widgets[$id];

            if ($widget->order !== $position) {
                $widget->order = $position;
                $widget->saveQuietly();
            }
        }

        $this->widgets->flush();

        $message = __('Widget order saved.');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'widgets' => $ordered]);
        }

        return redirect()
            ->route($this->routeName($request, 'index'))
            ->with('status', $message);
    }

    /**
     * Called after a widget is added or saved
     */
    protected function saved(Request $request, WidgetInstance $widget): void {}

    /**
     * Called after a widget is removed
     */
    protected function deleted(Request $request, WidgetInstance $widget): void {}

    protected function savedResponse(Request $request, WidgetInstance $widget, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            $location = $widget->getLocation() ?? WidgetLocation::make($widget->location);

            return new JsonResponse([
                'message' => $message,
                'widget' => $this->widgetData($request, $location, $widget),
            ], $status);
        }

        return redirect()
            ->route($this->routeName($request, 'index'))
            ->with('status', $message);
    }

    protected function form(Request $request, WidgetInstance|Widget $widget, WidgetLocation $location): WidgetForm
    {
        return WidgetForm::for($widget, $location, WidgetRoutes::guard($request));
    }

    /**
     * What the edit page (for a new or an existing widget) gets
     *
     * @return array<string, mixed>
     */
    protected function editData(Request $request, WidgetLocation $location, Widget $widget, ?WidgetInstance $instance, WidgetForm $form): array
    {
        $inertia = $this->views->isInertia();

        return [
            'form' => $inertia ? $form->render('json') : $form,
            'location' => $location->toArray(),
            'widget' => $widget->toArray(),
            'instance' => $instance ? $this->widgetData($request, $location, $instance) : null,
            'index_url' => route($this->routeName($request, 'index')),
            'strings' => $this->strings(),
            'layout' => config('widgets.layout', 'layouts.app'),
            'layout_section' => config('widgets.layout_section', 'content'),
        ];
    }

    /**
     * The locations the user can see, for the index page
     *
     * @return array<int, array<string, mixed>>
     */
    protected function locationsData(Request $request): array
    {
        $gate = $this->gate($request);
        $locations = [];

        foreach ($this->widgets->locations() as $location) {
            if (! $gate->allows('view', $location)) {
                continue;
            }

            $can_manage = $gate->allows('manage', $location);
            $widgets = $this->widgets->widgets($location);

            $locations[] = [
                ...$location->toArray(),
                'can_manage' => $can_manage,
                'is_full' => $location->getMax() !== null && $widgets->count() >= $location->getMax(),
                'order_url' => route($this->routeName($request, 'order'), $location->getKey()),
                'create_url' => route($this->routeName($request, 'create'), $location->getKey()),
                'widgets' => $widgets->map(fn (WidgetInstance $widget) => $this->widgetData($request, $location, $widget))->values()->all(),
                'available' => $can_manage ? array_values(array_map(fn (Widget $widget) => [
                    ...$widget->toArray(),
                    'create_url' => route($this->routeName($request, 'create'), [$location->getKey(), 'type' => $widget::key()]),
                ], $this->widgets->available($location))) : [],
            ];
        }

        return $locations;
    }

    /**
     * @return array<string, mixed>
     */
    protected function widgetData(Request $request, WidgetLocation $location, WidgetInstance $widget): array
    {
        $definition = $widget->definition();

        return [
            'id' => $widget->getKey(),
            'type' => $widget->type,
            'location' => $widget->location,
            'title' => $widget->displayTitle(),
            'name' => $definition?->name() ?? $widget->type,
            'icon' => $definition?->icon(),
            'missing' => $definition === null,
            'order' => $widget->order,
            'edit_url' => route($this->routeName($request, 'edit'), [$location->getKey(), $widget->getKey()]),
            'delete_url' => route($this->routeName($request, 'destroy'), [$location->getKey(), $widget->getKey()]),
        ];
    }

    /**
     * The strings the pages show, in the current locale
     *
     * @return array<string, string>
     */
    protected function strings(): array
    {
        return [
            'title' => __('Widgets'),
            'description' => __('Add widgets to each location, put them in order and configure them.'),
            'empty' => __('No widgets yet.'),
            'no_locations' => __('There are no widget locations.'),
            'add' => __('Add widget'),
            'add_to' => __('Add a widget to :location'),
            'choose' => __('Choose a widget'),
            'edit' => __('Edit'),
            'remove' => __('Remove'),
            'confirm_remove' => __('Remove this widget?'),
            'full' => __('This location is full.'),
            'missing' => __('This widget type is no longer available.'),
            'back' => __('Back to widgets'),
            'cancel' => __('Cancel'),
            'new_title' => __('Add :widget to :location'),
            'edit_title' => __('Edit :widget in :location'),
            'handle' => __('Move :widget'),
            'save_order' => __('Save order'),
            'order_saved' => __('Widget order saved.'),
            'order_failed' => __('The order couldn\'t be saved. Try again.'),
            'instructions' => __('To move a widget with the keyboard, press Space on its handle, use the arrow keys, then press Space again to drop it or Escape to cancel.'),
            'picked_up' => __(':widget picked up. Position :position of :total.'),
            'moved' => __(':widget moved to position :position of :total.'),
            'dropped' => __(':widget dropped at position :position of :total.'),
            'cancelled' => __('Move cancelled. :widget is back at position :position of :total.'),
        ];
    }

    protected function findLocation(string $key): WidgetLocation
    {
        return $this->widgets->findLocation($key) ?? throw new NotFoundHttpException;
    }

    protected function findType(WidgetLocation $location, string $type): Widget
    {
        $widget = $this->widgets->find($type);

        if (! $widget || ! $location->allows($widget)) {
            throw new NotFoundHttpException;
        }

        return $widget;
    }

    protected function findWidget(WidgetLocation $location, string $id): WidgetInstance
    {
        $widget = $this->widgets->model()::query()->inLocation($location)->find($id);

        return $widget ?? throw new NotFoundHttpException;
    }

    protected function definitionOf(WidgetInstance $widget): Widget
    {
        return $widget->definition() ?? throw new NotFoundHttpException;
    }

    /**
     * Refuse a new widget in a full location
     */
    protected function ensureRoomIn(WidgetLocation $location, string $field): void
    {
        $max = $location->getMax();

        if ($max === null || $this->widgets->model()::query()->inLocation($location)->count() < $max) {
            return;
        }

        throw ValidationException::withMessages([
            $field => trans_choice('{1} :location holds only one widget.|[2,*] :location holds only :count widgets.', $max, [
                'location' => $location->getLabel(),
                'count' => $max,
            ]),
        ]);
    }

    protected function ensureCanMoveTo(Request $request, WidgetInstance $widget, string $target): void
    {
        $location = $this->widgets->findLocation($target);

        if (! $location || ! $location->allows($widget->type) || ! $this->gate($request)->allows('manage', $location)) {
            throw ValidationException::withMessages([
                WidgetForm::LOCATION => __('The widget can\'t be moved there.'),
            ]);
        }

        $this->ensureRoomIn($location, WidgetForm::LOCATION);
    }

    protected function authorizeFor(Request $request, string $ability, mixed $argument): void
    {
        $this->gate($request)->authorize($ability, $argument);
    }

    protected function gate(Request $request): Gate
    {
        $gate = app(Gate::class);

        $user = $this->user($request);

        return $user ? $gate->forUser($user) : $gate;
    }

    protected function user(Request $request): ?Model
    {
        $user = $request->user(WidgetRoutes::guard($request));

        return $user instanceof Model ? $user : null;
    }

    protected function routeName(Request $request, string $action): string
    {
        return WidgetRoutes::namePrefix($request) . ".{$action}";
    }
}
