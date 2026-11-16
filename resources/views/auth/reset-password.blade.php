@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <h1>Reset password</h1>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <label for="password">New password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">

        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

        <button type="submit">Reset password</button>
    </form>
@endsection
