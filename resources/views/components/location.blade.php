@if($widgets->isNotEmpty() || $always)
<div {{ $attributes->class(['widgets', "widgets-{$name}"]) }} data-widget-location="{{ $name }}">
@foreach($widgets as $widget)
{{ $renderer->renderWidget($widget, $location) }}
@endforeach
</div>
@endif
