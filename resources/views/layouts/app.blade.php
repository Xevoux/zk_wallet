<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ZK Payment - Pembayaran Digital Privat')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app-container">
        @include('layouts.partials.navbar')

        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="footer">
            <p>&copy; 2024 ZK Payment - Sistem Pembayaran Digital dengan Zero-Knowledge Proof</p>
        </footer>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

