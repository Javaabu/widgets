<?php

namespace Javaabu\Widgets\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Widgets\Facades\Widgets;
use Javaabu\Widgets\Models\WidgetInstance;
use Javaabu\Widgets\WidgetLocation;

/**
 * Who may see and change widgets
 *
 * | Ability                        | Permission                                                      |
 * | ------------------------------ | --------------------------------------------------------------- |
 * | viewAny                        | view_widgets, edit_widgets, or the permission of any location   |
 * | view (a location)              | view_widgets, or what manage needs                              |
 * | manage, create, reorder (a location) | the location's permission (WidgetLocation::permission()), else edit_widgets |
 * | update, delete (a widget)      | manage on the widget's location                                 |
 *
 * Override it with `config('widgets.policy')`, `config('permissions.policies')`
 * or `Gate::policy()` for WidgetLocation and the widget model in the app.
 */
class WidgetPolicy
{
    public const string VIEW = 'view_widgets';

    public const string EDIT = 'edit_widgets';

    public function viewAny(Model $user): bool
    {
        $permissions = [self::VIEW, self::EDIT];

        foreach (Widgets::locations() as $location) {
            if ($location->getPermission()) {
                $permissions[] = $location->getPermission();
            }
        }

        return $this->allows($user, ...array_unique($permissions));
    }

    public function view(Model $user, WidgetLocation $location): bool
    {
        if ($this->allows($user, self::VIEW)) {
            return true;
        }

        return $this->manage($user, $location);
    }

    /**
     * Add, configure, order and remove the location's widgets
     */
    public function manage(Model $user, WidgetLocation $location): bool
    {
        return $this->allows($user, $location->getPermission() ?? self::EDIT);
    }

    public function create(Model $user, WidgetLocation $location): bool
    {
        return $this->manage($user, $location);
    }

    public function reorder(Model $user, WidgetLocation $location): bool
    {
        return $this->manage($user, $location);
    }

    public function update(Model $user, WidgetInstance $widget): bool
    {
        $location = $widget->getLocation();

        return $location !== null && $this->manage($user, $location);
    }

    public function delete(Model $user, WidgetInstance $widget): bool
    {
        return $this->update($user, $widget);
    }

    /**
     * Whether the user has any of the permissions
     */
    protected function allows(Model $user, string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
