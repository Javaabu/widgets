@extends($layout)

@section($layout_section)
    @manifestRequire('widgets::admin')
    @php($client_strings = \Illuminate\Support\Arr::only($strings, ['handle', 'picked_up', 'moved', 'dropped', 'cancelled', 'order_saved', 'order_failed', 'confirm_remove']))

    <div class="flex flex-col gap-6" data-widgets-admin data-strings="{{ json_encode($client_strings, JSON_UNESCAPED_UNICODE) }}">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold">{{ $strings['title'] }}</h1>
            <p class="text-muted-foreground">{{ $strings['description'] }}</p>
        </div>

        @if($status)
            <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
        @endif

        <p id="widgets-instructions" class="sr-only">{{ $strings['instructions'] }}</p>
        <div class="sr-only" aria-live="assertive" aria-atomic="true" data-widgets-announcer></div>

        @forelse($locations as $location)
            @include('widgets::partials.location', ['location' => $location])
        @empty
            <x-forms::alert type="info">{{ $strings['no_locations'] }}</x-forms::alert>
        @endforelse
    </div>
@endsection
