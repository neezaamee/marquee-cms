@extends('layouts.admin')

@section('title', 'Edit Booking')

@section('content')
    <livewire:booking-edit :booking="$booking" />
@endsection
