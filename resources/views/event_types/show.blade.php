@extends('layouts.admin')

@section('title', 'Event Type Details')

@section('content')
    <livewire:event-type-view :eventType="$eventType" />
@endsection
