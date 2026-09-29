@php($prefix = $tip?->id ?? 'new')
<div class="field-grid">
    <div class="field">
        <label for="area-{{ $prefix }}">Area</label>
        <input id="area-{{ $prefix }}" name="area" maxlength="40" required value="{{ $tip?->area ?? old('area') }}" placeholder="e.g. Submitting">
    </div>
    <div class="field">
        <label for="pages-{{ $prefix }}">Pages</label>
        <select id="pages-{{ $prefix }}" name="pages[]" multiple size="4">
            @foreach ($pageOptions as $page)
                <option value="{{ $page }}" @selected(in_array($page, $tip?->pages ?? [], true))>{{ $page }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="field" style="margin-top:var(--sp-md);">
    <label for="text-{{ $prefix }}">Tip</label>
    <textarea id="text-{{ $prefix }}" name="text" rows="2" maxlength="300" required>{{ $tip?->text ?? old('text') }}</textarea>
</div>
<label style="display:flex;align-items:center;gap:var(--sp-sm);margin:var(--sp-md) 0 var(--sp-lg);font-size:var(--text-sm);">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($tip?->isActive ?? true)> Active
</label>
