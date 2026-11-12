<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Brgy. San Rafael Service Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="login-wrap">
    <div style="width: 100%; max-width: @yield('width', '400px');">
        <div class="text-center mb-4">
            <div class="login-logo"><i class="bi bi-building"></i></div>
            <h1 class="h4 fw-bold mb-1">Barangay San Rafael</h1>
            <p class="text-secondary mb-0">Community Service Request Management System</p>
        </div>

        @yield('content')

        <p class="text-center text-secondary small mt-4 mb-0">@yield('footnote', 'Brgy. San Rafael · Barangay hall records system')</p>
    </div>
</div>
</body>
</html>
