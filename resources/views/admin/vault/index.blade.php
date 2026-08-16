@extends('admin.layouts.admin')

@section('title', 'Vault Items - ' . $project->title())

@section('content')
    <div class="max-w-4xl mx-auto py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Vault Items for: {{ $project->title() }}</h1>
            <a href="{{ route('admin.projects.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded px-3 py-1">
                Back to Projects
            </a>
        </div>

        <div class="bg-white p-6 rounded shadow mb-8">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Add New Vault Item</h2>
            @include('admin.vault._form', ['projectId' => $project->id()])
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Current Vault Items</h2>
            <div id="vault-items-list">
                @include('admin.vault._list', ['items' => $items, 'projectId' => $project->id()])
            </div>
        </div>
    </div>
@endsection
