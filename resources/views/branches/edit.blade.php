@extends('layouts.admin')

@section('title', 'Edit Branch')

@section('content')
    <livewire:branch-form :branch="$branch" />
@endsection
