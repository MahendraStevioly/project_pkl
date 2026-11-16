@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1>Forgot password</h1>
    <p>Request a one-time verification code to reset your password.</p>
    <form method="POST" action="{{ route('password.otp.send') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
        <button type="submit">Send code</button>
    </form>
    <p><a href="{{ route('login') }}">Back to login</a></p>
@endsection
