@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h1>Login</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">

        <label for="remember">
            <input id="remember" name="remember" type="checkbox" value="1">
            Remember me
        </label>

        <button type="submit">Login</button>
    </form>
    <p><a href="{{ route('password.request') }}">Forgot password?</a></p>
    <p><a href="{{ route('register') }}">Register</a></p>
@endsection
