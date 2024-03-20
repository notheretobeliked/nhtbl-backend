<div class="flex {{ count($images) < 1 && 'justify-center' }} flex-row w-full overflow-x-scroll hide-scrollbar snap-x snap-mandatory md:snap-none h-96 md:h-[40vh] gap-4 overflow-y-hidden galleryContainer">
    @foreach ($images as $image)
      <x-image-output :image="$image" size="large" caption class="h-80 md:h-[40vh]" />
    @endforeach
</div>
