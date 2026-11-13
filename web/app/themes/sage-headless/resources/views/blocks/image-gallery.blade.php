{{--
  Editor preview only. The frontend Svelte component handles the crossfade
  cycle; here we just show the first image so editors can see what's set.
--}}
@php
    $aspectClass = match ($aspect_ratio) {
        '1:1' => 'aspect-square',
        '16:9' => 'aspect-video',
        '4:3' => 'aspect-[4/3]',
        default => '',
    };
    $widthClass = $full_width ? 'w-full' : 'w-auto';
    $heightClass = $full_height ? 'h-full' : 'h-auto';
    $first = is_array($images) && count($images) > 0 ? $images[0] : null;
@endphp

<div {{ $attributes }} class="acf-image-gallery {{ $widthClass }} {{ $heightClass }}">
    @php
        $bgHex = [
            'nhtbl-grey-base' => '#d9d9d9', 'white' => '#ffffff', 'black' => '#000000',
            'nhtbl-green-base' => '#e0ff00', 'nhtbl-purple-base' => '#d59ce5', 'nhtbl-purple-light' => '#e4d5e8',
        ][$image_background_colour] ?? '#d9d9d9';
    @endphp
    <div class="relative {{ $aspectClass }} overflow-hidden" style="background-color: {{ $image_background ? $bgHex : '#f3f4f6' }}">
        @if ($first)
            <img
                src="{{ $first['sizes']['large'] ?? $first['url'] ?? '' }}"
                alt="{{ $first['alt'] ?? '' }}"
                class="w-full h-full object-cover"
            />
            @if (count($images) > 1)
                <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded">
                    Cycles {{ count($images) }} images every {{ $interval_ms }}ms
                </div>
            @endif
        @else
            <div class="flex items-center justify-center h-32 text-sm text-gray-500">
                Add at least 1 image
            </div>
        @endif
    </div>
    @if ($caption)
        <p class="mt-2 text-sm">{!! $caption !!}</p>
    @endif
</div>
