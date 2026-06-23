@extends('layouts.admin')

@section('title', 'Goods Receiving Note Form')

@section('content')
    <livewire:purchases.goods-receiving-form :id="$id" />
@endsection
