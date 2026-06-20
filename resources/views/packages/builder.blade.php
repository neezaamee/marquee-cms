@extends('layouts.admin')

@section('title', 'Package Builder')

@section('content')
    <livewire:package-builder :package="$package" />
@endsection
