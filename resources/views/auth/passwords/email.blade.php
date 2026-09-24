@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="row flex-between-center mb-2">
  <div class="col-auto">
    <h5>Reset Password</h5>
  </div>
  <div class="col-auto fs-10 text-600">
    <a href="{{ route('login') }}">Back to login</a>
  </div>
</div>

<p class="fs-10 text-600 mb-3">
  Enter your registered email address and we will send you a password reset link.
</p>

@if (session('status'))
  <div class="alert alert-success fs-10 mb-3" role="alert">
    {{ session('status') }}
  </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
  @csrf

  <!-- Email Address -->
  <div class="mb-3">
    <label class="form-label" for="email">Email address</label>
    <input class="form-control @error('email') is-invalid @enderror" 
           id="email" 
           type="email" 
           name="email" 
           placeholder="name@example.com" 
           value="{{ old('email') }}" 
           required 
           autofocus />
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Submit Button -->
  <div class="mb-3">
    <button class="btn btn-primary d-block w-100 mt-3" type="submit">Send Reset Link</button>
  </div>
</form>
@endsection
