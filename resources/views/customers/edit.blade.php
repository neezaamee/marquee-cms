@extends('layouts.admin')

@section('title', 'Edit Customer')

@section('content')
    <livewire:customer-form :customer="$customer" />
@endsection
