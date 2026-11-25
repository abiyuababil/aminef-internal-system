@extends('layouts.auth')
@section('title', 'Set New Password')

@section('content')
<div class="auth-card-header">
    <h1>Set New Password</h1>
    <p>Choose a strong password (minimum 8 characters).</p>
</div>
<div class="auth-card-body">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required>
        </div>
        <div class="form-group">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn-login-primary">Update Password</button>
    </form>
</div>
@endsection
