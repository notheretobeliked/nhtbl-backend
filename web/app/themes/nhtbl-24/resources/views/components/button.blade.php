@props([
  'label' => null,
  'url' => null
])

<a class="p-3 border rounded-md border-black hover:bg-nhtbl-green-base" href="{!! $url !!}">
    <p>{!! $label !!}</p>
</a>