@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <h1>Register</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">

        <label for="role">Registration type</label>
        <select id="role" name="role" required>
            <option value="magang" @selected(old('role') === 'magang')>Internship</option>
            <option value="karyawan" @selected(old('role') === 'karyawan')>Employment</option>
        </select>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

        <button type="submit">Register</button>
    </form>
    <p><a href="{{ route('login') }}">Login</a></p>
@endsection
