<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e5a24">
    <meta name="description" content="Staff sign-in for Barangay San Rafael's service request records.">
    <title>@yield('title') | Brgy. San Rafael Service Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="login-wrap" id="main">
    <div class="login-column">
        <div class="login-brand">
            <div class="login-logo"><i class="bi bi-building" aria-hidden="true"></i></div>
            <div>
                <h1>Barangay San Rafael</h1>
                <p>Service Request Management System</p>
            </div>
        </div>

        @yield('content')

        <p class="login-note">For Barangay San Rafael staff only.</p>
    </div>
</main>
</body>
</html>
