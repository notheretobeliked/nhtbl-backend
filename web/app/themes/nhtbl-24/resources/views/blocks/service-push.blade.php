<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
  @if ($items)
    <ul>
      @foreach ($items as $item)
        <li>@dump( $item)</li>
      @endforeach
    </ul>
  @else
    <p>{{ $block->preview ? 'Add an item...' : 'No items found!' }}</p>
  @endif


</div>
