<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e5a24">
    <title>@yield('title') · Brgy. San Rafael Service Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($user = auth()->user())
<body data-reopen-modal="@yield('reopenModal')">
<div class="app-shell">
    {{-- Phone/tablet top bar; pages are reached from the tab bar at the bottom. --}}
    <header class="mobile-topbar d-lg-none">
        <div class="brand-icon"><i class="bi bi-layers-fill"></i></div>
        <div class="brand-title">Brgy. San Rafael</div>
    </header>

    <aside class="app-sidebar offcanvas-lg offcanvas-start" id="appSidebar" tabindex="-1" aria-label="Main menu">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-layers-fill"></i></div>
            <div>
                <div class="brand-title">Brgy. San Rafael</div>
                <div class="brand-sub">{{ $user->isResident() ? 'Resident Portal' : 'Service Management' }}</div>
            </div>
            <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas"
                    data-bs-target="#appSidebar" aria-label="Close menu"></button>
        </div>

        <nav class="nav flex-column gap-1 p-2">
            @if ($user->isResident())
                {{-- Resident navigation map (Figure 3) --}}
                <a class="nav-link @if (request()->routeIs('resident.dashboard')) active @endif" href="{{ route('resident.dashboard') }}">
                    <i class="bi bi-house"></i> Dashboard
                </a>
                <a class="nav-link @if (request()->routeIs('resident.requests.*')) active @endif" href="{{ route('resident.requests.create') }}">
                    <i class="bi bi-file-earmark-plus"></i> New Request
                </a>
                <a class="nav-link @if (request()->routeIs('resident.tickets.*')) active @endif" href="{{ route('resident.tickets.index') }}">
                    <i class="bi bi-chat-left-text"></i> Support Tickets
                </a>
            @else
                {{-- Staff and Administrator navigation map (Figure 4) --}}
                <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                    <i class="bi bi-house"></i> Dashboard
                </a>
                <a class="nav-link @if (request()->routeIs('residents.*')) active @endif" href="{{ route('residents.index') }}">
                    <i class="bi bi-people"></i> Residents
                </a>
                <a class="nav-link @if (request()->routeIs('service-requests.*')) active @endif" href="{{ route('service-requests.index') }}">
                    <i class="bi bi-clipboard-check"></i> Service Requests
                </a>
                <a class="nav-link @if (request()->routeIs('tickets.*')) active @endif" href="{{ route('tickets.index') }}">
                    <i class="bi bi-inbox"></i> Ticket Inbox
                </a>
                @can('administer')
                    <a class="nav-link @if (request()->routeIs('admin.service-types.*')) active @endif" href="{{ route('admin.service-types.index') }}">
                        <i class="bi bi-list-check"></i> Service Types
                    </a>
                @endcan
                <a class="nav-link @if (request()->routeIs('reports')) active @endif" href="{{ route('reports') }}">
                    <i class="bi bi-bar-chart"></i> Reports
                </a>
                @can('administer')
                    <a class="nav-link @if (request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-person-gear"></i> User Management
                    </a>
                @endcan
            @endif
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('password.edit') }}" class="d-flex align-items-center gap-2 px-2 mb-2 text-white text-decoration-none" title="Change password">
                <div class="avatar">{{ $user->initials() }}</div>
                <div class="lh-sm">
                    <div class="fw-semibold small">{{ $user->name }}</div>
                    <div class="user-role">{{ $user->position ?? $user->role->label() }}</div>
                </div>
            </a>
            <button type="button" class="nav-link w-100 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </button>
        </div>
    </aside>

    <main class="app-main">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- Phone/tablet tab bar: the most used pages one tap away; Menu opens the full sidebar. --}}
<nav class="bottom-nav d-lg-none" aria-label="Main">
    @if ($user->isResident())
        <a href="{{ route('resident.dashboard') }}" @class(['active' => request()->routeIs('resident.dashboard')]) @if (request()->routeIs('resident.dashboard')) aria-current="page" @endif>
            <i class="bi bi-house"></i><span>Home</span>
        </a>
        <a href="{{ route('resident.requests.create') }}" @class(['active' => request()->routeIs('resident.requests.*')]) @if (request()->routeIs('resident.requests.*')) aria-current="page" @endif>
            <i class="bi bi-file-earmark-plus"></i><span>New Request</span>
        </a>
        <a href="{{ route('resident.tickets.index') }}" @class(['active' => request()->routeIs('resident.tickets.*')]) @if (request()->routeIs('resident.tickets.*')) aria-current="page" @endif>
            <i class="bi bi-chat-left-text"></i><span>Tickets</span>
        </a>
    @else
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')]) @if (request()->routeIs('dashboard')) aria-current="page" @endif>
            <i class="bi bi-house"></i><span>Home</span>
        </a>
        <a href="{{ route('service-requests.index') }}" @class(['active' => request()->routeIs('service-requests.*')]) @if (request()->routeIs('service-requests.*')) aria-current="page" @endif>
            <i class="bi bi-clipboard-check"></i><span>Requests</span>
        </a>
        <a href="{{ route('residents.index') }}" @class(['active' => request()->routeIs('residents.*')]) @if (request()->routeIs('residents.*')) aria-current="page" @endif>
            <i class="bi bi-people"></i><span>Residents</span>
        </a>
        <a href="{{ route('tickets.index') }}" @class(['active' => request()->routeIs('tickets.*')]) @if (request()->routeIs('tickets.*')) aria-current="page" @endif>
            <i class="bi bi-inbox"></i><span>Tickets</span>
        </a>
    @endif
    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar"
            @class(['active' => request()->routeIs('reports', 'admin.*', 'password.*')])>
        <i class="bi bi-list"></i><span>Menu</span>
    </button>
</nav>

{{-- Screen 15: Log out confirmation, available from the sidebar of every screen --}}
<div class="modal fade modal-sheet" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <h2 class="h5 fw-bold mb-2" id="logoutModalLabel">Log Out</h2>
                <p class="text-secondary mb-4">Are you sure you want to log out of your account?</p>
                <form method="POST" action="{{ route('logout') }}" class="sheet-actions d-flex justify-content-end gap-2">
                    @csrf
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</div>

@stack('modals')
</body>
</html>
