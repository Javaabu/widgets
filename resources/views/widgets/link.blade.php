@if(filled($settings['title'] ?? null))
<h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
@if(filled($settings['description'] ?? null))
<p class="widget-description">{{ $settings['description'] }}</p>
@endif
<a class="widget-button" href="{{ $url }}"@if(! empty($settings['new_tab'])) target="_blank" rel="noopener noreferrer"@elseif($external) rel="noopener"@endif>{{ $settings['label'] }}</a>
