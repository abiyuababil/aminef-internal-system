@extends('layouts.auth')
@section('title', 'Staff Login')

@section('content')
<div class="auth-card-header">
    <h1>Staff Portal Login</h1>
    <p>Sign in with your AMINEF office email and password.</p>
</div>
<div class="auth-card-body">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@aminef.or.id">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-size:12.5px;">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="{{ route('password.request') }}">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login-primary">Sign In</button>
    </form>

    @if(app()->environment('local'))
        <div style="margin-top:18px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;font-size:12px;color:#475569;">
            <strong>Demo Accounts (password: <code>password123</code>):</strong><br>
            Admin: <code>admin@aminef.or.id</code><br>
            HRD: <code>hrd@aminef.or.id</code><br>
            Supervisor: <code>supervisor@aminef.or.id</code><br>
            Staff: <code>staff@aminef.or.id</code>
        </div>
    @endif
</div>
@endsection
