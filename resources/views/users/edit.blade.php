@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <livewire:user-form :user="$user" />
@endsection
