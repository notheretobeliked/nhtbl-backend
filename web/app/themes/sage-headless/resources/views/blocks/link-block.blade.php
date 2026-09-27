@unless ($block->preview)
  <div {{ $attributes }}>
@endunless



@if ($link_type === 'internal' && $internal_link)
@unless ($block->preview)

<a href="{{ get_permalink($internal_link->ID) }}" class="internal-link">
  @endunless

@elseif ($link_type === 'external' && $external_link)
@unless ($block->preview)

  <a href="{{ $external_link['url'] }}" 
     @if($external_link['target']) target="{{ $external_link['target'] }}" @endif
     class="external-link">
    {{ $external_link['title'] ?: $external_link['url'] }}
    @endunless
@else
  <p>{{ $block->preview ? 'Configure your link...' : 'No link configured!' }}</p>
@endif

<div>
  <InnerBlocks template="{{ $block->template }}" />
</div>


@unless ($block->preview)
</a>
  </div>
@endunless
