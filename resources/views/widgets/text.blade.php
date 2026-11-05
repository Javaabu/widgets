@if(filled($settings['title'] ?? null))
<h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
@if(filled($settings['text'] ?? null))
<div class="widget-text">{{ $settings['text'] }}</div>
@endif
