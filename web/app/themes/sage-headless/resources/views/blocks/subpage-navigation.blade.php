{{--
  Editor preview only. The frontend Svelte component renders the real nav from
  the resolved `navItems` GraphQL field; here we just indicate the source.
--}}
<nav {{ $attributes }} class="subpage-navigation text-sm">
    <span class="text-gray-500">
        Subpage navigation —
        {{ $nav_source === 'subpages' ? 'child pages of this page' : 'sibling pages' }}
        (rendered on the front end).
    </span>
</nav>
