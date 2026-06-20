@extends('layouts.admin')

@section('title', 'Package Preview')

@section('content')
    <livewire:package-preview :package="$package" />
@endsection
