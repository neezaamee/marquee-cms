@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="row flex-between-center mb-2">
  <div class="col-auto">
    <h5>Log in</h5>
  </div>
  <div class="col-auto fs-10 text-600">
    <span class="mb-0">or</span> 
    <a href="{{ route('register') }}">Create an account</a>
  </div>
</div>

<form method="POST" action="{{ route('login') }}">
  @csrf

  <!-- Email or Username -->
  <div class="mb-3">
    <label class="form-label" for="login">Email or Username</label>
    <input class="form-control @error('login') is-invalid @enderror" 
           id="login" 
           type="text" 
           name="login" 
           placeholder="Email or Username" 
           value="{{ old('login') }}" 
           required 
           autofocus />
    @error('login')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Password -->
  <div class="mb-3">
    <label class="form-label" for="password">Password</label>
    <div class="input-group">
      <input class="form-control @error('password') is-invalid @enderror" 
             id="password" 
             type="password" 
             name="password" 
             placeholder="Password" 
             required />
      <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
        <span class="fas fa-eye"></span>
      </button>
      @error('password')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <div class="row flex-between-center">
    <!-- Remember Me -->
    <div class="col-auto">
      <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" />
        <label class="form-check-label mb-0" for="remember">Remember me</label>
      </div>
    </div>
    
    <!-- Forgot Password (Placeholder path for now) -->
    <div class="col-auto">
      <a class="fs-10" href="#">Forgot Password?</a>
    </div>
  </div>

  <!-- Submit Button -->
  <div class="mb-3">
    <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Log in</button>
  </div>
</form>

<div class="position-relative mt-4">
  <hr />
  <div class="divider-content-center">or log in with</div>
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

<script>
  function togglePassword(id, btn) {
    const input = document.getElementById(id);
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
