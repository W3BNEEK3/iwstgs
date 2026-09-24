@if (empty($items))
    <div class="empty-state">
        <x-ui.icon name="folder_open" :size="32" />
        <p>No vault items yet.</p>
    </div>
@else
    <div class="table-frame">
        <table class="data-table">
            <thead><tr><th>Type</th><th>Title</th><th>Content</th><th></th></tr></thead>
            <tbody>
                @foreach ($items as $item)
                    @include('admin.vault._row', ['item' => $item, 'projectId' => $projectId])
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mobile-row-cards">
        @foreach ($items as $item)
            @include('admin.vault._card', ['item' => $item, 'projectId' => $projectId])
        @endforeach
    </div>
@endif
