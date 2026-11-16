@extends('layouts.app')

@section('title', 'Edit admin')

@section('content')
    <h1>Edit admin</h1>
    @include('operator.admins.form', [
        'action' => route('operator.admins.update', $admin),
        'method' => 'PUT',
        'admin' => $admin,
        'passwordRequired' => false,
    ])
    <a href="{{ route('operator.admins.index') }}">Back to admins</a>
@endsection
