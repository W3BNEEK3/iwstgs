{{-- Bridges every existing admin/* page (which still does @extends('admin.layouts.admin')
     and @section('content')) onto the new admin shell, without editing any of
     those ~15 files. This is the "unify admin under one shell" step from the
     design doc §8.4 — the content pages themselves (raw tables, inline styles)
     are a separate follow-up migration, not done here.

     'title' is deliberately untouched here — each admin page already sets its
     own @section('title', 'Projects') etc., and that flows straight through
     to layouts/app.blade.php's @yield('title', ...) unchanged. Redefining it
     in this bridge would execute after the leaf page's own section and
     silently overwrite it, since Blade sections aren't parent-yields-to-child
     by default — last @section() to run wins. --}}
@extends('layouts.shells.admin')

@section('body')
    @yield('content')
@endsection
