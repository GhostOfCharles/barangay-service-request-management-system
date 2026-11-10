<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e5a24">
    <meta name="description" content="Barangay San Rafael's internal record of residents, service requests and follow-ups.">
    <title>@yield('title') | Brgy. San Rafael Service Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-reopen-modal="@yield('reopenModal')">
<a class="skip visually-hidden-focusable" href="#main">Skip to content</a>
<div class="app-shell">
    {{-- Phone/tablet top bar: opens the sidebar as a slide-out menu --}}
    <header class="mobile-topbar d-lg-none">
        <button type="button" class="btn-menu" data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                aria-controls="appSidebar" aria-label="Open menu">
            <i class="bi bi-list fs-3" aria-hidden="true"></i>
        </button>
        <div class="brand-icon"><i class="bi bi-layers-fill" aria-hidden="true"></i></div>
        <div class="brand-title">Brgy. San Rafael</div>
    </header>

    <aside class="app-sidebar offcanvas-lg offcanvas-start" id="appSidebar" tabindex="-1" aria-label="Main menu">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-layers-fill" aria-hidden="true"></i></div>
            <div>
                <div class="brand-title">Brgy. San Rafael</div>
                <div class="brand-sub">Service Management</div>
            </div>
            <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas"
                    data-bs-target="#appSidebar" aria-label="Close menu"></button>
        </div>

        <nav class="nav flex-column">
            <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="bi bi-house" aria-hidden="true"></i> Dashboard
            </a>
            <a class="nav-link @if (request()->routeIs('residents.*')) active @endif" href="{{ route('residents.index') }}" @if (request()->routeIs('residents.*')) aria-current="page" @endif>
                <i class="bi bi-people" aria-hidden="true"></i> Residents
            </a>
            <a class="nav-link @if (request()->routeIs('service-requests.*')) active @endif" href="{{ route('service-requests.index') }}" @if (request()->routeIs('service-requests.*')) aria-current="page" @endif>
                <i class="bi bi-clipboard-check" aria-hidden="true"></i> Service Requests
            </a>
            <a class="nav-link @if (request()->routeIs('reports')) active @endif" href="{{ route('reports') }}" @if (request()->routeIs('reports')) aria-current="page" @endif>
                <i class="bi bi-bar-chart" aria-hidden="true"></i> Reports
            </a>
            @can('manage-users')
                <a class="nav-link @if (request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}" @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    <i class="bi bi-person-gear" aria-hidden="true"></i> User Management
                </a>
            @endcan
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('password.edit') }}" class="sidebar-user" title="Change password">
                <div class="avatar" aria-hidden="true">{{ auth()->user()->initials() }}</div>
                <div>
                    <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ auth()->user()->position ?? auth()->user()->role->label() }}</div>
                </div>
            </a>
            <button type="button" class="nav-link w-100 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log Out
            </button>
        </div>
    </aside>

    <main class="app-main" id="main">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Screen 8: Log out confirmation, available from every board --}}
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body p-4">
                <h2 class="mb-1" id="logoutModalLabel">Log Out</h2>
                <p class="text-secondary mb-3">Are you sure you want to log out?</p>
                <form method="POST" action="{{ route('logout') }}" class="d-flex gap-2">
                    @csrf
                    <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success flex-fill">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</div>

@stack('modals')
</body>
</html>
