<form method="POST" action="{{ route('admin.projects.vault.store', $projectId) }}">
    @csrf

    <div class="field-grid">
        <x-forms.select name="document_type" label="Type" required :options="[
            'business_context' => 'Business Context',
            'prd' => 'PRD',
            'srs' => 'SRS',
            'sad' => 'SAD',
            'coding_guidelines' => 'Coding Guidelines',
            'glossary' => 'Glossary',
            'sprint_goal_template' => 'Sprint Goal Template',
        ]" />
        <x-forms.text-input name="title" label="Title" :value="old('title')" required />
    </div>

    <div style="margin-top:var(--sp-lg);">
        <x-forms.textarea name="content" label="Content" :value="old('content')" required :rows="4" />
    </div>

    <div class="field-grid" style="margin-top:var(--sp-lg);">
        <x-forms.text-input name="display_order" type="number" min="1" label="Display Order" :value="old('display_order', 1)" required />
        <x-forms.select name="phase_gate" label="Phase Gate" placeholder="(None)" :options="[
            'pre_induction' => 'Pre-Induction',
            'post_induction' => 'Post-Induction',
            'post_sprint_1' => 'Post-Sprint 1',
            'mid_session' => 'Mid-Session',
            'advanced_only' => 'Advanced Only',
        ]" />
    </div>

    <div style="margin-top:var(--sp-lg);">
        <x-forms.text-input name="rank_gate" label="Rank Gate" help="Optional — e.g. Junior, Mid, Senior" :value="old('rank_gate')" />
    </div>

    <label style="display:flex;align-items:center;gap:8px;margin-top:var(--sp-lg);">
        <input type="checkbox" name="is_reference_doc" value="1" @checked(old('is_reference_doc'))>
        Reference document (shown to learners on the sprint board)
    </label>

    <x-ui.button type="submit" severity="primary" icon="add" style="margin-top:var(--sp-lg);">Add Item</x-ui.button>
</form>
