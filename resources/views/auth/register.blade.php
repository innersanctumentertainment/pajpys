<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>
        <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <input type="text" name="name" value="{{ old('name') }}" required>
        <input type="email" name="email" value="{{ old('email') }}" required>
        <input type="password" name="password" required>
        <input type="password" name="password_confirmation" required>
        <select name="role" required>
            <option value="client">Post jobs (Client)</option>
            <option value="provider">Post services (Provider)</option>
            <option value="va">Virtual Assistant</option>
            <option value="client_provider">Post jobs &amp; services</option>
            <option value="both">Client &amp; Virtual Assistant</option>
        </select>
        <button type="submit">Register</button>
    </form>
</body>
</html>
