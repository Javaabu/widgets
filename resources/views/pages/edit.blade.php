@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-widget-edit="{{ $widget['key'] }}">
        <div class="flex flex-col gap-1">
            <a href="{{ $index_url }}" class="text-sm text-muted-foreground hover:underline">{{ $strings['back'] }}</a>
            <h1 class="text-2xl font-semibold">{{ str_replace([':widget', ':location'], [$widget['name'], $location['label']], $instance ? $strings['edit_title'] : $strings['new_title']) }}</h1>
            @if($widget['description'])
                <p class="text-muted-foreground">{{ $widget['description'] }}</p>
            @endif
        </div>

        {{ $form }}
    </div>
@endsection
