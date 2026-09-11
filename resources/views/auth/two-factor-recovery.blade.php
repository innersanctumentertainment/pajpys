@extends('layouts.auth')

@section('title', 'Recovery Codes')

@section('content')
    <article>
        <header>
            <h1>Recovery codes</h1>
            <p>Store these codes in a safe place. Each code can only be used once.</p>
        </header>

        @if (count($recoveryCodes) > 0)
            <ul>
                @foreach ($recoveryCodes as $code)
                    <li><code>{{ $code }}</code></li>
                @endforeach
            </ul>
        @else
            <p>No recovery codes available. Regenerate them from your security settings.</p>
        @endif

        <footer>
            <p><a href="{{ route('dashboard') }}">Continue to dashboard</a></p>
        </footer>
    </article>
@endsection
