<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
    @if ($services)
    <div class="group relative mb-12 transition-colors duration-300 hover:bg-nhtbl-purple-base px-4 py-4">
        <div class="inset-0 flex flex-row gap-3">
          <div class="aspect-[4/3] w-80 overflow-hidden relative">
            <img src="{!! get_the_post_thumbnail_url($services[0], 'post-thumbnail') !!}" alt="Thumbnail" class="!w-full !h-full object-cover" />
          </div>
          <div class="group-hover:bg-nhtbl-purple-base transition-colors duration-300 flex flex-col gap-4">
            <InnerBlocks template="{{ $block->template }}" />
            <div>
                <x-button label="Read more" :url="get_the_permalink($services[0])" />
            </div>
          </div>
        </div>
      </div>
          @else
        <p>{{ $block->preview ? 'Add an item...' : 'No items found!' }}</p>
    @endif
</div>


