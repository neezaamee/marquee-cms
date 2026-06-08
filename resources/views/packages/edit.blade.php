@extends('layouts.admin')

@section('title', 'Edit Package')

@section('content')
    <livewire:package-form :package="$package" />
@endsection
