@extends('layouts.admin')

@section('title', 'Booking Slip')

@section('content')
    <livewire:booking-slip :booking="$booking" />
@endsection
