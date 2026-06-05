@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="row flex-between-center mb-2">
  <div class="col-auto">
    <h5>Register</h5>
  </div>
  <div class="col-auto fs-10 text-600">
    <span class="mb-0">Have an account?</span> 
    <a href="{{ route('login') }}">Login</a>
  </div>
</div>

<form method="POST" action="{{ route('register') }}">
  @csrf

  <!-- Name -->
  <div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input class="form-control @error('name') is-invalid @enderror" 
           id="name" 
           type="text" 
           name="name" 
           placeholder="Full Name" 
           value="{{ old('name') }}" 
           required 
           autofocus />
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Email Address -->
  <div class="mb-3">
    <label class="form-label" for="email">Email address</label>
    <input class="form-control @error('email') is-invalid @enderror" 
           id="email" 
           type="email" 
           name="email" 
           placeholder="Email address" 
           value="{{ old('email') }}" 
           required />
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Password & Confirm Password -->
  <div class="row gx-2">
    <div class="mb-3 col-sm-6">
      <label class="form-label" for="password">Password</label>
      <input class="form-control @error('password') is-invalid @enderror" 
             id="password" 
             type="password" 
             name="password" 
             placeholder="Password" 
             required />
      @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
    
    <div class="mb-3 col-sm-6">
      <label class="form-label" for="password_confirmation">Confirm Password</label>
      <input class="form-control" 
             id="password_confirmation" 
             type="password" 
             name="password_confirmation" 
             placeholder="Confirm Password" 
             required />
    </div>
  </div>

  <!-- Terms Acceptance (Placeholder) -->
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="terms" name="terms" required />
    <label class="form-label" for="terms">I accept the <a href="#!">terms</a> and <a class="white-space-nowrap" href="#!">privacy policy</a></label>
  </div>

  <!-- Submit Button -->
  <div class="mb-3">
    <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Register</button>
  </div>
</form>

<div class="position-relative mt-4">
  <hr />
  <div class="divider-content-center">or register with</div>
</div>

<div class="row g-2 mt-2">
  <div class="col-sm-6">
    <a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#">
      <span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google
    </a>
  </div>
  <div class="col-sm-6">
    <a class="btn btn-outline-facebook btn-sm d-block w-100" href="#">
      <span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook
    </a>
  </div>
</div>
@endsection
