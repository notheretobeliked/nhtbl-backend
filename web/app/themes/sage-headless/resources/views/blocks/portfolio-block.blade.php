{{--
  Editor preview only — static, no links, no hover. The front-end Svelte
  component does the real interactive render. Colours come from the block's
  configured background/text (via $block->classes); muted text uses opacity and
  borders use currentColor so the preview adapts to the chosen palette.
--}}
<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
  @if ($portfolioItems && count($portfolioItems) > 0)
    <div class="py-8">

      @if($enableSearch)
        <!-- Controls (static) -->
        <div class="alignwide pb-8">
          <div class="my-4 flex flex-row items-center gap-2 opacity-50">
            <div class="w-full p-3 border border-current rounded-full text-sm">Search projects…</div>
            <span class="flex-shrink-0 rounded-full border border-current px-3 py-1.5 text-sm whitespace-nowrap">Filter by service</span>
            <span class="flex-shrink-0 rounded-full border border-current px-3 py-1.5 text-sm whitespace-nowrap">Mode ▾</span>
          </div>
          <p class="opacity-60">
            {{ count($portfolioItems) }} project{{ count($portfolioItems) !== 1 ? 's' : '' }} total
          </p>
        </div>
      @endif

      @if($displayMode === 'vertical_list')
        <!-- Vertical / list view -->
        <div class="alignwide space-y-4 pb-7">
          @foreach ($portfolioItems as $item)
            <div class="featured-project p-2 grid grid-cols-[1fr_4fr] gap-4">
              <div class="aspect-[4/3]">
                @if($item['featured_image_url'])
                  <img src="{{ $item['featured_image_url'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover rounded" />
                @endif
              </div>
              <div class="flex flex-col">
                <h3 class="text-lg font-display mb-2">{{ $item['title'] }}</h3>
                @if($item['excerpt'])
                  <div class="mb-4 opacity-70">{!! $item['excerpt'] !!}</div>
                @endif
                @if($item['client_names'] || $item['year_range'])
                  <p class="text-sm opacity-60">
                    @if($item['client_names'])
                      With/for: {{ $item['client_names'] }}
                      @if($item['year_range']) {{ $item['year_range'] }} @endif
                    @elseif($item['year_range'])
                      {{ $item['year_range'] }}
                    @endif
                  </p>
                @endif
                @if(count($item['service_names']) > 0)
                  <div class="services flex flex-row gap-2 mt-4 flex-wrap">
                    @foreach($item['service_names'] as $serviceName)
                      <div class="font-sans text-sm rounded-full border border-current px-2 py-0 whitespace-nowrap">{{ $serviceName }}</div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>

      @elseif($displayMode === 'horizontal_scroll')
        <!-- Horizontal scrolling view -->
        <div class="alignfull">
          <div class="flex gap-7 overflow-x-auto pb-4 px-4">
            @foreach ($portfolioItems as $item)
              <div class="featured-project w-72 flex-shrink-0">
                @if($item['featured_image_url'])
                  <img src="{{ $item['featured_image_url'] }}" alt="{{ $item['title'] }}" class="w-full h-auto object-contain" />
                @endif
                <p class="mt-2 font-display">{{ $item['title'] }}</p>
              </div>
            @endforeach
          </div>
        </div>

      @else
        <!-- Masonry grid view (default) -->
        <div class="alignfull">
          <div class="grid gap-7 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 px-4">
            @foreach ($portfolioItems as $item)
              <div class="featured-project">
                @if($item['featured_image_url'])
                  <img src="{{ $item['featured_image_url'] }}" alt="{{ $item['title'] }}" class="w-full h-auto object-contain" />
                @endif
                <p class="mt-2 font-display">{{ $item['title'] }}</p>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>

  @else
    <div class="py-8">
      <div class="alignwide text-center">
        <p class="opacity-70 text-lg">
          {{ $block->preview ? 'Configure your portfolio block settings…' : 'No projects found!' }}
        </p>
        @if ($block->preview)
          <p class="text-sm opacity-50 mt-2">
            Display Mode: {{ $displayMode ?? 'not set' }}<br>
            Project Source: {{ $projectSource ?? 'not set' }}<br>
            Enable Search: {{ $enableSearch ? 'Yes' : 'No' }}
          </p>
        @endif
      </div>
    </div>
  @endif
</div>
