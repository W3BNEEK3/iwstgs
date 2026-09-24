{{-- Deliberately bar-less: no background, border, or shadow (design doc §9).
     Right-aligned: notifications, then the profile menu (theme choice + logout
     live there — the header spec has no room for a standalone toggle). --}}
<div class="app-topbar">
    <button type="button" class="btn-icon mobile-menu-btn" data-mobile-drawer-toggle aria-label="Open menu">
        <x-ui.icon name="menu" />
    </button>

    <span class="spacer"></span>

    <button type="button" class="btn-icon" aria-label="Notifications">
        <span class="notif-dot" aria-hidden="true"></span>
        <x-ui.icon name="notifications" />
    </button>

    <div style="position:relative;">
        <button type="button" style="border:none;background:none;padding:0;cursor:pointer;display:block;" data-dropdown-toggle="profile-dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
            <x-ui.avatar :name="auth()->user()->name ?? null" />
        </button>

        <div id="profile-dropdown" class="profile-dropdown" data-dropdown hidden>
            <div class="profile-dropdown-label">Theme</div>
            <div class="theme-switch">
                <button type="button" data-theme-choice="light">Light</button>
                <button type="button" data-theme-choice="dark">Dark</button>
                <button type="button" data-theme-choice="system">System</button>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="profile-dropdown-item">
                    <x-ui.icon name="logout" :size="18" />
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
