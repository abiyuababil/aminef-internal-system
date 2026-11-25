@extends('layouts.app')
@section('title', 'Document Templates')

@section('content')
<ol class="breadcrumb">
    <li>HRD Management</li>
    <li>/</li>
    <li>Templates</li>
</ol>
<h1 class="page-title">HRD Document Templates</h1>

<div class="aminef-card">
    <div class="aminef-card-header">
        <h2 class="aminef-card-title">Upload / Update Comp-Time Template</h2>
    </div>
    <div class="aminef-card-body">
        @php $ctTpl = $templates->get('comp_time_template'); @endphp
        @if($ctTpl)
            <div class="alert alert-info">
                Current Active Template: <strong>{{ $ctTpl->original_name }}</strong> (Updated {{ $ctTpl->updated_at->format('d M Y H:i') }}) &mdash;
                <a href="{{ route('comp-time.template') }}">Download</a>
            </div>
        @endif

        <form method="POST" action="{{ route('hrd.templates.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="key" value="comp_time_template">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Template Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ $ctTpl->title ?? 'Comp-Time Request Form Template' }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Template File (PDF/Word/Excel) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save-green">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
