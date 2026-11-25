@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<ol class="breadcrumb">
    <li>Overview</li>
    <li>/</li>
    <li>My Profile</li>
</ol>
<h1 class="page-title">My Profile &amp; Electronic Signature</h1>

<div class="form-grid-2">
    <div class="aminef-card">
        <div class="aminef-card-header">
            <h2 class="aminef-card-title">Profile &amp; Saved Signature</h2>
        </div>
        <div class="aminef-card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Direct Supervisor</label>
                        <input type="text" class="form-control" value="{{ $user->supervisor?->name ?? '-' }}" disabled>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" value="{{ old('department', $user->department) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-control" value="{{ old('position', $user->position) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Electronic Signature (Saved for future requests/approvals)</label>
                    @if($user->signature_path)
                        <div style="margin-bottom:8px;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;">
                            <div style="font-size:11.5px;color:#64748b;margin-bottom:4px;">Current Saved Signature:</div>
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Saved Signature" style="max-height:70px;">
                        </div>
                    @endif
                    <div class="sig-pad-wrap">
                        <canvas id="profileSigCanvas" class="sig-canvas"></canvas>
                    </div>
                    <input type="hidden" name="signature_data" id="profileSigInput">
                    <button type="button" id="profileSigClear" class="btn-secondary" style="margin-top:6px;padding:4px 12px;font-size:12px;">Clear Signature</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="aminef-card">
        <div class="aminef-card-header">
            <h2 class="aminef-card-title">Change Password</h2>
        </div>
        <div class="aminef-card-body">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save-green">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    initSignaturePad('profileSigCanvas', 'profileSigInput', 'profileSigClear');
</script>
@endpush
