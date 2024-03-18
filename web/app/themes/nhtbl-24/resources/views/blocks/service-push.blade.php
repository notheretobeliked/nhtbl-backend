<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
    @if ($services)
        <div class="grid grid-cols-2 gap-7">
            <div class="h-full flex flex-col justify-between">
                <div>
                    <h2>{{ get_the_title($services[0]) }}</h2>
                    {{ get_the_excerpt($services[0]) }}
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
