@extends('layouts.admin')

@section('title', 'Booking Slip')

@section('content')
    <livewire:booking-slip-v3 :booking="$booking" />
@endsection
