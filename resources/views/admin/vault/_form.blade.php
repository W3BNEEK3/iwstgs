<form hx-post="{{ route('admin.projects.vault.store', $projectId) }}"
      hx-target="#vault-items-list"
      hx-swap="innerHTML"
      hx-on::after-request="this.reset()"
      class="space-y-4">
    @csrf
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <select name="document_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm" required>
                <option value="business_context">Business Context</option>
                <option value="prd">PRD</option>
                <option value="srs">SRS</option>
                <option value="sad">SAD</option>
                <option value="coding_guidelines">Coding Guidelines</option>
                <option value="glossary">Glossary</option>
                <option value="sprint_goal_template">Sprint Goal Template</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input type="text" name="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm" required>
        </div>
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700">Content</label>
        <textarea name="content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm" required></textarea>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Display Order</label>
            <input type="number" name="display_order" value="1" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Phase Gate</label>
            <select name="phase_gate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black sm:text-sm">
                <option value="">(None)</option>
                <option value="pre_induction">Pre-Induction</option>
                <option value="post_induction">Post-Induction</option>
                <option value="post_sprint_1">Post-Sprint 1</option>
                <option value="mid_session">Mid-Session</option>
                <option value="advanced_only">Advanced Only</option>
            </select>
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="is_reference_doc" value="1" class="rounded border-gray-300 text-black focus:ring-black">
                <span class="text-sm font-medium text-gray-700">Is Reference Doc</span>
            </label>
        </div>
    </div>

    <div class="pt-2">
        <button type="submit" class="bg-black text-white px-4 py-2 rounded shadow hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
            Add Item
        </button>
    </div>
</form>
