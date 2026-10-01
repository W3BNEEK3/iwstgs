@extends('layouts.shells.dashboard')

@section('title', "What's new — " . config('app.name', 'Areyna'))

@section('body')
<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">What's new</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">New things for learners, newest first. Tiroco mentions each one once; they all stay here.</p>
</div>

@if ($announcements === [])
    <div class="empty-state">
        <x-ui.icon name="campaign" :size="32" />
        <p>Nothing new yet. When something new arrives for learners, you'll find it here.</p>
    </div>
@else
    <div class="whats-new">
        @foreach ($announcements as $announcement)
            <article class="panel whats-new-item">
                <h2>{{ $announcement->title }}</h2>
                @if ($announcement->publishedAt)
                    <time datetime="{{ $announcement->publishedAt }}">{{ \Illuminate\Support\Carbon::parse($announcement->publishedAt)->format('j M Y') }}</time>
                @endif
                <p>{{ $announcement->body }}</p>
                @if ($announcement->linkUrl)
                    <a href="{{ $announcement->linkUrl }}">Take a look</a>
                @endif
            </article>
        @endforeach
    </div>
@endif
@endsection
