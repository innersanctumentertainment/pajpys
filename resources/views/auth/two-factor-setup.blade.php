@extends('layouts.auth')

@section('title', 'Two-Factor Setup')

@section('content')
    <article>
        <header>
            <h1>Set up two-factor authentication</h1>
            <p>Scan the QR code with your authenticator app, then enter the generated code.</p>
        </header>

        <section aria-label="Authenticator setup">
            <p>Secret key: <code>{{ $secret }}</code></p>
            <div aria-label="Two-factor authentication QR code">{!! $qrCodeInline !!}</div>
        </section>

        <form method="POST" action="{{ route('two-factor.setup.confirm') }}">
            @csrf

            <p>
                <label for="code">Authentication code</label>
                <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code">
            </p>

            <p>
                <button type="submit">Confirm and enable</button>
            </p>
        </form>
    </article>
@endsection
