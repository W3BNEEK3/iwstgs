{{-- Expects: $item = ['route' => '...', 'label' => '...', 'icon' => 'material_symbol_name', 'active' => 'optional.wildcard.*'] --}}
@php
    $isActive = request()->routeIs($item['active'] ?? $item['route']);
@endphp
<a href="{{ route($item['route']) }}" class="nav-item @if ($isActive) is-active @endif">
    <x-ui.icon :name="$item['icon']" />
    <span class="nav-label">{{ $item['label'] }}</span>
</a>
