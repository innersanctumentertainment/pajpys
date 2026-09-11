@extends('layouts.auth')

@section('title', 'Two-Factor Challenge')

@section('content')
    <article>
        <header>
            <h1>Two-factor authentication</h1>
            <p>Enter the code from your authenticator app or use a recovery code.</p>
        </header>

        <form method="POST" action="{{ route('two-factor.verify') }}">
            @csrf

            <fieldset>
                <legend>Verification</legend>

                <p>
                    <label for="code">Authentication code</label>
                    <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code">
                </p>

                <p>
                    <label for="recovery_code">Recovery code</label>
                    <input id="recovery_code" type="text" name="recovery_code" autocomplete="off">
                </p>
            </fieldset>

            <p>
                <button type="submit">Verify</button>
            </p>
        </form>
    </article>
@endsection
