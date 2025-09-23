<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
  @if ($portfolioItems && count($portfolioItems) > 0)
    <div class="bg-black min-h-screen text-white">
      
      @if($enableSearch)
        <!-- Header with controls (static for headless) -->
        <div class="alignwide pt-24 pb-8">
          <!-- Search Box (static) -->
          <div class="my-4 flex flex-row gap-2">
            <input
              type="text"
              placeholder="Search projects by title, client, service, or description..."
              class="w-full p-3 bg-gray-900 text-white border border-gray-700 rounded-lg focus:border-white focus:outline-none"
              disabled
            />

            <!-- View Mode Toggle (static) -->
            <div class="flex gap-1">
              <button aria-label="List view" class="opacity-50" disabled>
                <svg preserveAspectRatio="" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6">
                  <rect x="0.5" y="0.5" width="54" height="54" rx="8.5" class="stroke-white" />
                  <rect x="5.5" y="9.5" width="14" height="8" rx="0.5" class="stroke-white fill-white" />
                  <rect x="5.5" y="23.5" width="14" height="8" rx="0.5" class="stroke-white fill-white" />
                  <rect x="5.5" y="37.5" width="14" height="8" rx="0.5" class="stroke-white fill-white" />
                  <path d="M22 10.25H49M22 13.5167H49M22 17.25H49" class="stroke-white" />
                  <path d="M22 24.25H49M22 27.5167H49M22 31.25H49" class="stroke-white" />
                  <path d="M22 38.25H49M22 41.5167H49M22 45.25H49" class="stroke-white" />
                </svg>
              </button>
              <button aria-label="Masonry view" class="opacity-50" disabled>
                <svg preserveAspectRatio="" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6">
                  <rect x="0.5" y="0.5" width="54" height="54" rx="8.5" class="stroke-white fill-white" />
                  <rect x="6" y="8" width="20" height="12" class="stroke-white fill-black" />
                  <rect x="6" y="22" width="20" height="10" class="stroke-white fill-black" />
                  <rect x="6" y="34" width="43" height="14" class="stroke-white fill-black" />
                  <rect x="29" y="8" width="20" height="24" class="stroke-white fill-black" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Results count -->
          <p class="text-gray-400 mb-6">
            {{ count($portfolioItems) }} project{{ count($portfolioItems) !== 1 ? 's' : '' }} total
          </p>
        </div>
      @endif

      @if($displayMode === 'vertical_list')
        <!-- Vertical List View -->
        <div class="alignwide space-y-4 pb-7">
          @foreach ($portfolioItems as $item)
            <article class="featured-project p-2 group hover:bg-white duration-300 transition-all rounded-lg hover:!text-black">
              <a class="grid grid-cols-[1fr_4fr] gap-4" href="{{ $item['url'] }}">
                <!-- Image -->
                <div class="mb-4 aspect-[4/3]">
                  @if($item['featured_image_url'])
                    <img src="{{ $item['featured_image_url'] }}" 
                         alt="{{ $item['title'] }}"
                         class="w-full h-full object-cover rounded" />
                  @endif
                </div>
                <div class="flex flex-col">
                  <!-- Heading -->
                  <h3 class="text-lg font-display mb-2">{{ $item['title'] }}</h3>

                  <!-- Content -->
                  @if($item['excerpt'])
                    <div class="mb-4">{!! $item['excerpt'] !!}</div>
                  @endif

                  <!-- Clients and Year -->
                  @if($item['client_names'] || $item['year_range'])
                    <p class="text-sm text-gray-600">
                      @if($item['client_names'])
                        With/for: {{ $item['client_names'] }}
                        @if($item['year_range']) {{ $item['year_range'] }} @endif
                      @elseif($item['year_range'])
                        {{ $item['year_range'] }}
                      @endif
                    </p>
                  @endif

                  <!-- Services -->
                  @if(count($item['service_names']) > 0)
                    <div class="services flex flex-row gap-2 mt-4 flex-wrap">
                      @foreach($item['service_names'] as $serviceName)
                        <div class="group-hover:border-black font-sans text-sm rounded-full border border-white px-2 py-0 whitespace-nowrap">{{ $serviceName }}</div>
                      @endforeach
                    </div>
                  @endif
                </div>
              </a>
            </article>
          @endforeach

          @if(count($portfolioItems) === 0)
            <div class="text-center py-12">
              <p class="text-gray-400 text-lg">No projects found.</p>
            </div>
          @endif
        </div>

      @elseif($displayMode === 'horizontal_scroll')
        <!-- Horizontal Scrolling View -->
        <div class="alignfull">
          <div class="flex gap-7 overflow-x-auto pb-4 px-4" style="scroll-snap-type: x mandatory;">
            @foreach ($portfolioItems as $item)
              <article class="featured-project flex-shrink-0" style="width: 300px; scroll-snap-align: start;">
                <a href="{{ $item['url'] }}">
                  <div class="cursor-pointer relative group">
                    @if($item['featured_image_url'])
                      <img src="{{ $item['featured_image_url'] }}" 
                           alt="{{ $item['title'] }}"
                           class="w-full h-auto object-contain" />
                    @endif
                    
                    <!-- Hover overlay -->
                    <div class="bg-nhtbl-green-base p-3 bg-opacity-90 absolute inset-0 flex flex-col justify-center content-center items-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                      <p class="text-black text-xl font-display text-center w-full">{{ $item['title'] }}</p>
                      @if($item['client_names'])
                        <p class="text-black text-small md:text-base mt-2 font-display text-center w-full">{{ $item['client_names'] }}</p>
                      @endif
                      @if($item['year_range'])
                        <p class="text-black text-small md:text-base mt-1 font-display text-center w-full">{{ $item['year_range'] }}</p>
                      @endif
                      @if(count($item['service_names']) > 0)
                        <div class="services flex flex-row gap-1 mt-2 flex-wrap justify-center">
                          @foreach($item['service_names'] as $serviceName)
                            <div class="font-sans text-xs rounded-full border border-black px-2 py-0 whitespace-nowrap text-black">{{ $serviceName }}</div>
                          @endforeach
                        </div>
                      @endif
                    </div>
                  </div>
                </a>
              </article>
            @endforeach
          </div>

          @if(count($portfolioItems) === 0)
            <div class="text-center py-12 alignwide">
              <p class="text-gray-400 text-lg">No projects found.</p>
            </div>
          @endif
        </div>

      @else
        <!-- Masonry Grid View (default) -->
        <div class="alignfull">
          <div class="grid gap-7 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 px-4">
            @foreach ($portfolioItems as $item)
              <article class="featured-project group">
                <a href="{{ $item['url'] }}">
                  <div class="cursor-pointer relative">
                    @if($item['featured_image_url'])
                      <img src="{{ $item['featured_image_url'] }}" 
                           alt="{{ $item['title'] }}"
                           class="w-full h-auto object-contain" />
                    @endif
                    
                    <!-- Hover overlay -->
                    <div class="bg-nhtbl-green-base p-3 bg-opacity-90 absolute inset-0 flex flex-col justify-center content-center items-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                      <p class="text-black text-xl font-display text-center w-full">{{ $item['title'] }}</p>
                      @if($item['client_names'])
                        <p class="text-black text-small md:text-base mt-2 font-display text-center w-full">{{ $item['client_names'] }}</p>
                      @endif
                      @if($item['year_range'])
                        <p class="text-black text-small md:text-base mt-1 font-display text-center w-full">{{ $item['year_range'] }}</p>
                      @endif
                      @if(count($item['service_names']) > 0)
                        <div class="services flex flex-row gap-1 mt-2 flex-wrap justify-center">
                          @foreach($item['service_names'] as $serviceName)
                            <div class="font-sans text-xs rounded-full border border-black px-2 py-0 whitespace-nowrap text-black">{{ $serviceName }}</div>
                          @endforeach
                        </div>
                      @endif
                    </div>
                  </div>
                </a>
              </article>
            @endforeach
          </div>

          @if(count($portfolioItems) === 0)
            <div class="text-center py-12 alignwide">
              <p class="text-gray-400 text-lg">No projects found.</p>
            </div>
          @endif
        </div>
      @endif
    </div>

  @else
    <div class="bg-black min-h-screen text-white">
      <div class="alignwide pt-24 pb-8 text-center">
        <p class="text-gray-400 text-lg">
          {{ $block->preview ? 'Configure your portfolio block settings...' : 'No projects found!' }}
        </p>
        @if ($block->preview)
          <p class="text-sm text-gray-600 mt-2">
            Display Mode: {{ $displayMode ?? 'not set' }}<br>
            Project Source: {{ $projectSource ?? 'not set' }}<br>
            Enable Search: {{ $enableSearch ? 'Yes' : 'No' }}
          </p>
        @endif
      </div>
    </div>
  @endif
</div>