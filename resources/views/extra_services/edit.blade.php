@extends('layouts.admin')

@section('title', 'Edit Add-on')

@section('content')
    <livewire:extra-service-form :extraService="$extraService" />
@endsection
