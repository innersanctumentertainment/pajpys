<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
        <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" required>
        <input type="password" name="password" required>
        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit">Login</button>
    </form>
</body>
</html>
