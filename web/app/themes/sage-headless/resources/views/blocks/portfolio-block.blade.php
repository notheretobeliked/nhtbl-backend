<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
  @if ($portfolioItems)
    <ul class="grid grid-cols-3 gap-7"  x-data x-masonry.wait.2500>
      @foreach ($portfolioItems as $item)
      <li>
        <a href="{!!$item["url"]!!}">{!!$item["image"]!!}</a>
      </li>
      @endforeach
    </ul>
  @else
    <p>{{ $block->preview ? 'Add an item...' : 'No items found!' }}</p>
  @endif

</div>
