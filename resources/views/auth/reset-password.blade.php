@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <article>
        <header>
            <h1>Choose a new password</h1>
        </header>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <p>
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email">
            </p>

            <p>
                <label for="password">New password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </p>

            <p>
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </p>

            <p>
                <button type="submit">Reset password</button>
            </p>
        </form>
    </article>
@endsection
