{{--
  Editor preview only. The frontend Svelte component handles the crossfade
  cycle and navigation; here we just render the inner slides so editors can
  build and reorder them. InnerBlocks is restricted to acf/slide.
--}}
<div {{ $attributes }} class="acf-slideshow">
    <InnerBlocks
        template="{{ $block->template }}"
        allowedBlocks='["acf/slide"]'
        orientation="horizontal"
    />
    @if (! $show_navigation)
        <p class="mt-2 text-xs text-gray-500">Navigation hidden on the front end.</p>
    @endif
</div>
