@extends('layouts.admin')

@section('title', 'Customer Profile')

@section('content')
    <livewire:customer-profile :customer="$customer" />
@endsection
