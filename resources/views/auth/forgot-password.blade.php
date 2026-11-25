@extends('layouts.auth')
@section('title', 'Forgot Password')

@section('content')
<div class="auth-card-header">
    <h1>Reset Password</h1>
    <p>Enter your office email to receive a password reset link.</p>
</div>
<div class="auth-card-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>

        <button type="submit" class="btn-login-primary">Send Reset Link</button>
        <div style="text-align:center;margin-top:14px;font-size:12.5px;">
            <a href="{{ route('login') }}">Back to Login</a>
        </div>
    </form>
</div>
@endsection
