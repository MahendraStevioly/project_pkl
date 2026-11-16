@extends('layouts.guest')

@section('title', 'Verify password reset code')

@section('content')
    <h1>Verify password reset code</h1>
    <form method="POST" action="{{ route('password.otp.verify') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">

        <label for="code">Six-digit code</label>
        <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">

        <button type="submit">Verify code</button>
    </form>
@endsection
