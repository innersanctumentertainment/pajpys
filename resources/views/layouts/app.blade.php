<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'PAJPYS — Hire the right virtual assistant. Get things done. Caribbean\'s premier VA marketplace.')">
    <meta name="theme-color" content="#0a1628">
    <title>@yield('title', 'PAJPYS') — Hire the right virtual assistant</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
