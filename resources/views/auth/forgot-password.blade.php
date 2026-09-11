@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <article>
        <header>
            <h1>Reset your password</h1>
            <p>Enter your email address and we will send you a reset link.</p>
        </header>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <p>
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </p>

            <p>
                <button type="submit">Send reset link</button>
            </p>
        </form>

        <footer>
            <p><a href="{{ route('login') }}">Back to login</a></p>
        </footer>
    </article>
@endsection
