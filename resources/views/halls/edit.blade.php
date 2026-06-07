@extends('layouts.admin')

@section('title', 'Edit Hall')

@section('content')
    <livewire:hall-form :hall="$hall" />
@endsection
