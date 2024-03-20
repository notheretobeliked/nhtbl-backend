<div class="snap-start">
  <figure>
    <picture class="block object-cover {{ $class }}">
      <img width="{!! $image['width'] !!}" height="{!! $image['height'] !!}"
        class="@if (!$customsize &! $crop) !w-auto max-w-none @elseif (!$crop) w-full @endif @if ($crop) {{ $class }} @endif object-cover !h-full object-center"
        src=" {!! $image['src'][0] !!}" srcset=" {!! $image['srcset'] !!}" alt="{!! $image['alt'] !!}" />
    </picture>
    @if ($caption)
      {!! $image['caption'] !!}
    @endif
  </figure>
</div>
