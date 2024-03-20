<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
    @if ($services)
        <div class="grid grid-cols-2 gap-7">
            <div class="h-full flex flex-col justify-between">
                <div>
                  <InnerBlocks template="{{ $block->template }}" />
                </div>
                <x-button label="Read more" />
            </div>
            <div class="aspect-square">
                <img src="{!! get_the_post_thumbnail_url($services[0], 'post-thumbnail') !!}" alt="Thumbnail" class="!w-full !h-full object-cover" />
            </div>
        </div>
    @else
        <p>{{ $block->preview ? 'Add an item...' : 'No items found!' }}</p>
    @endif
</div>
