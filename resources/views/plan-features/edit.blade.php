@extends('layouts.admin')

@section('title', 'Edit Plan Feature')

@section('content')
    <livewire:feature-form :feature="$feature" />
@endsection
