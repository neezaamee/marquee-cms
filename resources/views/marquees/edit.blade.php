@extends('layouts.admin')

@section('title', 'Edit Marquee')

@section('content')
    <livewire:marquee-form :marquee="$marquee" />
@endsection
