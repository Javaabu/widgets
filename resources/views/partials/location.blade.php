<x-forms::card :title="$location['label']" data-widgets-location="{{ $location['key'] }}">
    <div class="flex flex-col gap-4">
        @if($location['description'])
            <p class="text-sm text-muted-foreground">{{ $location['description'] }}</p>
        @endif

        @if($location['widgets'] === [])
            <p class="text-sm text-muted-foreground" data-widgets-empty>{{ $strings['empty'] }}</p>
        @else
            <form method="POST" action="{{ $location['order_url'] }}" data-widgets-order>
                @csrf
                @method('PUT')

                <ol class="flex flex-col gap-2" @if($location['can_manage']) data-widgets-sortable @endif aria-label="{{ $location['label'] }}">
                    @foreach($location['widgets'] as $widget)
                        <li class="flex items-center gap-3 rounded-md border bg-background p-3" data-widget-id="{{ $widget['id'] }}" data-widget-title="{{ $widget['title'] }}">
                            <input type="hidden" name="widgets[]" value="{{ $widget['id'] }}">

                            @if($location['can_manage'])
                                <button type="button"
                                        class="flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-muted focus-visible:outline-2"
                                        data-widgets-handle
                                        aria-describedby="widgets-instructions"
                                        aria-label="{{ str_replace(':widget', $widget['title'], $strings['handle']) }}">
                                    <x-forms::icon name="grip-vertical" />
                                </button>
                            @endif

                            <div class="flex min-w-0 flex-1 flex-col">
                                <span class="truncate font-medium" dir="auto">{{ $widget['title'] }}</span>
                                @if($widget['missing'])
                                    <span class="text-sm text-destructive">{{ $strings['missing'] }}</span>
                                @elseif($widget['title'] !== $widget['name'])
                                    <span class="text-sm text-muted-foreground">{{ $widget['name'] }}</span>
                                @endif
                            </div>

                            @if($location['can_manage'])
                                <div class="flex shrink-0 items-center gap-2">
                                    @unless($widget['missing'])
                                        <x-forms::link-button :url="$widget['edit_url']" color="outline" size="sm">{{ $strings['edit'] }}</x-forms::link-button>
                                    @endunless
                                    <x-forms::button type="submit" color="destructive" size="sm" form="widgets-remove-{{ $widget['id'] }}">{{ $strings['remove'] }}</x-forms::button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </form>

            @if($location['can_manage'])
                @foreach($location['widgets'] as $widget)
                    <form method="POST" action="{{ $widget['delete_url'] }}" id="widgets-remove-{{ $widget['id'] }}" data-widgets-confirm="{{ $strings['confirm_remove'] }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        @endif

        @if($location['can_manage'])
            @if($location['is_full'])
                <p class="text-sm text-muted-foreground">{{ $strings['full'] }}</p>
            @elseif($location['available'] !== [])
                <form method="GET" action="{{ $location['create_url'] }}" class="flex flex-wrap items-end gap-2" data-widgets-add>
                    <div class="flex min-w-48 flex-col gap-1">
                        <label for="widgets-add-{{ $location['key'] }}" class="text-sm font-medium">{{ str_replace(':location', $location['label'], $strings['add_to']) }}</label>
                        <select id="widgets-add-{{ $location['key'] }}" name="type" class="h-9 rounded-md border bg-background px-3" required>
                            @foreach($location['available'] as $widget)
                                <option value="{{ $widget['key'] }}">{{ $widget['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-forms::button type="submit" start-icon="plus">{{ $strings['add'] }}</x-forms::button>
                </form>
            @endif
        @endif
    </div>
</x-forms::card>
