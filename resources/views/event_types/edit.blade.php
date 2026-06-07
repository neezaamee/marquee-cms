@extends('layouts.admin')

@section('title', 'Edit Event Type')

@section('content')
    <livewire:event-type-form :eventType="$eventType" />
@endsection
