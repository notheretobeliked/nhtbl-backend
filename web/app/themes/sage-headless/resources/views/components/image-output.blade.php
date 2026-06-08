<div class="snap-start">
  <figure>
    <picture class="block object-cover {{ $class }}">
      <img width="{!! $image['width'] !!}" height="{!! $image['height'] !!}"
        class="@if (!empty($crop)) {{ $class }} @else{{ empty($size) ? ' w-full' : ' !w-auto max-w-none' }} @endif object-cover !h-full object-center"
        src=" {!! $image['src'][0] !!}" srcset=" {!! $image['srcset'] !!}" alt="{!! $image['alt'] !!}" />
    </picture>
    @if (!empty($caption))
      {!! $image['caption'] !!}
    @endif
  </figure>
</div>
