@extends('layouts.app')

@section('title', 'Create admin')

@section('content')
    <h1>Create admin</h1>
    @include('operator.admins.form', [
        'action' => route('operator.admins.store'),
        'method' => 'POST',
        'admin' => null,
        'passwordRequired' => true,
    ])
    <a href="{{ route('operator.admins.index') }}">Back to admins</a>
@endsection
