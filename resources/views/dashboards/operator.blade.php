@extends('layouts.app')

@section('title', 'Operator dashboard')

@section('content')
    <h1>Operator dashboard</h1>
    <p>Operator area.</p>
    <a href="{{ route('operator.admins.index') }}">Manage admins</a>
@endsection
