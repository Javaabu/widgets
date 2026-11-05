@if(filled($settings['title'] ?? null))
<h2 class="widget-title">{{ $settings['title'] }}</h2>
@endif
<figure class="widget-image">
@if($link)
<a href="{{ $link }}"><img src="{{ $src }}" alt="{{ $settings['alt'] ?? '' }}" loading="lazy"></a>
@else
<img src="{{ $src }}" alt="{{ $settings['alt'] ?? '' }}" loading="lazy">
@endif
@if(filled($settings['caption'] ?? null))
<figcaption>{{ $settings['caption'] }}</figcaption>
@endif
</figure>
