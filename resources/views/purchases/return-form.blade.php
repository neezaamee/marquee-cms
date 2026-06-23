@extends('layouts.admin')

@section('title', 'Purchase Return Form')

@section('content')
    <livewire:purchases.purchase-return-form :id="$id" />
@endsection
