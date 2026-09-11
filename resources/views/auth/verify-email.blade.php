@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')
    <article>
        <header>
            <h1>Verify your email address</h1>
            <p>Before accessing the marketplace, please verify your email address.</p>
        </header>

        <p>We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <p>
                <button type="submit">Resend verification email</button>
            </p>
        </form>

        <footer>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </footer>
    </article>
@endsection
