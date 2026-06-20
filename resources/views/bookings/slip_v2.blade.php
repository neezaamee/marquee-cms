@extends('layouts.admin')

@section('title', 'Booking Slip (V2)')

@section('content')
    <livewire:booking-slip-v2 :booking="$booking" />
@endsection
