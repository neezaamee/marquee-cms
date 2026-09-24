@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<div class="row flex-between-center mb-2">
  <div class="col-auto">
    <h5>Set New Password</h5>
  </div>
  <div class="col-auto fs-10 text-600">
    <a href="{{ route('login') }}">Back to login</a>
  </div>
</div>

<form method="POST" action="{{ route('password.update') }}">
  @csrf

  <input type="hidden" name="token" value="{{ $token }}">

  <!-- Email Address -->
  <div class="mb-3">
    <label class="form-label" for="email">Email address</label>
    <input class="form-control @error('email') is-invalid @enderror" 
           id="email" 
           type="email" 
           name="email" 
           value="{{ $email ?? old('email') }}" 
           required 
           autofocus />
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Password -->
  <div class="mb-3">
    <label class="form-label" for="password">New Password</label>
    <div class="input-group">
      <input class="form-control @error('password') is-invalid @enderror" 
             id="password" 
             type="password" 
             name="password" 
             placeholder="New Password" 
             required />
      <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
        <span class="fas fa-eye"></span>
      </button>
      @error('password')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <!-- Password Confirmation -->
  <div class="mb-3">
    <label class="form-label" for="password_confirmation">Confirm Password</label>
    <div class="input-group">
      <input class="form-control" 
             id="password_confirmation" 
             type="password" 
             name="password_confirmation" 
             placeholder="Confirm Password" 
             required />
      <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation', this)">
        <span class="fas fa-eye"></span>
      </button>
    </div>
  </div>

  <!-- Submit Button -->
  <div class="mb-3">
    <button class="btn btn-primary d-block w-100 mt-3" type="submit">Reset Password</button>
  </div>
</form>

<script>
  function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('span');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }
</script>
@endsection
