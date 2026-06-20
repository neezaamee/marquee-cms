@extends('layouts.admin')

@section('title', 'Edit Menu Item')

@section('content')
    <livewire:menu-item-form :menuItem="$menuItem" />
@endsection
