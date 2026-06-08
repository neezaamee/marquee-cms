@extends('layouts.admin')

@section('title', 'Edit Menu Category')

@section('content')
    <livewire:menu-category-form :menuCategory="$menuCategory" />
@endsection
