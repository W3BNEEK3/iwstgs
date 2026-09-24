{{-- Generic modal shell. Opened via a trigger anywhere with data-modal-open="{id}"
     (design doc §16 "Overlays: modal/dialog"). Prefer <x-overlays.confirm-modal>
     for the "are you sure" destructive-action pattern — it's built on this. --}}
@props(['id', 'title' => null])

<div id="{{ $id }}" class="modal-overlay">
    <div class="modal-box" role="dialog" aria-modal="true" @if ($title) aria-labelledby="{{ $id }}-title" @endif>
        @if ($title)
            <h3 id="{{ $id }}-title">{{ $title }}</h3>
        @endif
        {{ $slot }}
    </div>
</div>
