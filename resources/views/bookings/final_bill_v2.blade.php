@extends('layouts.admin')

@section('title', 'Sale Tax Invoice (V2) - #' . $booking->booking_number)

@section('content')
    <livewire:final-bill-invoice-v2 :booking="$booking" />
@endsection
