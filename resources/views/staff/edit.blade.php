@extends('layouts.admin')

@section('title', 'Edit Staff Member')

@section('content')
    <livewire:staff-form :staff="$staff" />
@endsection
