@if(count($items) === 0)
    <p class="text-gray-500 italic">No vault items have been added to this project yet.</p>
@else
    <ul class="space-y-4">
        @foreach($items as $item)
            @include('admin.vault._row', ['item' => $item, 'projectId' => $projectId])
        @endforeach
    </ul>
@endif
