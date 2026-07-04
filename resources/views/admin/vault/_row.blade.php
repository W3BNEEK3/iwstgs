<li class="border rounded p-4 bg-gray-50 flex justify-between items-start">
    <div>
        <div class="flex items-center space-x-2 mb-1">
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ $item->getDocumentType()->value }}</span>
            <span class="font-semibold text-gray-900">{{ $item->getTitle() }}</span>
        </div>
        <p class="text-sm text-gray-600 line-clamp-2 mt-2">{{ $item->toPrimitives()['content'] }}</p>
    </div>
    
    <button hx-delete="{{ route('admin.projects.vault.destroy', [$projectId, $item->getId()]) }}"
            hx-target="#vault-items-list"
            hx-swap="innerHTML"
            hx-confirm="Remove this vault item?"
            class="text-red-600 hover:text-red-900 text-sm font-medium border border-transparent hover:border-red-600 rounded px-2 py-1 transition-colors">
        Remove
    </button>
</li>
