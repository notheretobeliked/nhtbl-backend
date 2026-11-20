{{--
  Editor preview only. The frontend Svelte component renders the real strip from
  the portfolio's project images; here we just describe what will appear.
--}}
<div {{ $attributes }} class="image-strip text-sm">
    <span class="text-gray-500">
        Image strip — {{ $max_images }} images from across the portfolio,
        {{ $auto_scroll ? 'drifting sideways' : 'scrollable sideways' }}
        (rendered on the front end).
    </span>
</div>
