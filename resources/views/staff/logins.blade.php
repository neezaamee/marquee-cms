@extends('layouts.admin')

@section('title', 'Manage CMS Logins: ' . $staff->name)

@section('content')
    <livewire:manage-staff-logins :staff="$staff" />
@endsection
