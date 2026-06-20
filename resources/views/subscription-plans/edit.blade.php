@extends('layouts.admin')

@section('title', 'Edit Subscription Plan')

@section('content')
    <livewire:plan-form :plan="$plan" />
@endsection
