{{--
  Editor preview only. The frontend Svelte component renders the real slide
  (image cover + caption overlay); here we show the image and caption so
  editors can see what each slide holds.
--}}
<div {{ $attributes }} class="acf-slide relative bg-gray-100 overflow-hidden">
    @if ($image)
        <img
            src="{{ $image['sizes']['large'] ?? $image['url'] ?? '' }}"
            alt="{{ $image['alt'] ?? '' }}"
            class="w-full h-full object-cover aspect-[3/2]"
        />
    @else
        <div class="flex items-center justify-center h-40 text-sm text-gray-500">
            Select an image for this slide
        </div>
    @endif

    @if ($caption)
        <div class="absolute bottom-2 left-2 bg-white/80 text-black text-sm px-2 py-1 rounded">
            {!! $caption !!}
        </div>
    @endif
</div>
