@extends('layouts.admin')

@section('title', 'Booking Details')

@section('content')
    <livewire:booking-view :booking="$booking" />
@endsection
