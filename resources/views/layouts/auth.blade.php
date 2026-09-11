<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Authentication') — {{ config('app.name') }}</title>
</head>
<body>
    <header><nav aria-label="Authentication"><a href="{{ url('/') }}">{{ config('app.name') }}</a></nav></header>
    <main>
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</body>
</html>
