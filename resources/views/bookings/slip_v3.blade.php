@extends('layouts.admin')

@section('title', 'Booking Slip (V3)')

@section('content')
    <livewire:booking-slip-v3 :booking="$booking" />
@endsection
