<div class="{{ $block->classes }} relative" style="{{ $block->inlineStyle }}">
    <div class="w-full aspect-video">
        <img src={!! $images[0]['url'] !!}
            class="absolute top-0 left-0 bottom-0 right-0 w-full duration-1000 !h-full object-cover transition-all"
            alt="background" />
    </div>
    <div class="absolute flex top-0 h-full w-full p-8 items-center justify-center">
        <div
            class="relative w-full h-full bg-nhtbl-green-base flex justify-center items-center p-8 leading-relaxed text-black">
            <div class="max-w-[856px] font-serif text-xl md:text-2xl">
                <InnerBlocks template="{{ $block->template }}" />
            </div>
        </div>
    </div>
</div>
