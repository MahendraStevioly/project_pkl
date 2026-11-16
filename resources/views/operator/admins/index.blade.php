@extends('layouts.app')

@section('title', 'Manage admins')

@section('content')
    <h1>Manage admins</h1>
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif
    <p><a href="{{ route('operator.admins.create') }}">Create admin</a></p>
    <ul>
        @foreach ($admins as $admin)
            <li>
                {{ $admin->name }} ({{ $admin->email }})
                <a href="{{ route('operator.admins.edit', $admin) }}">Edit</a>
                <form method="POST" action="{{ route('operator.admins.destroy', $admin) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>
@endsection
