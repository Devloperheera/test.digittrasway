@extends('Website.Layout.master')

@section('custom_css')
    <style>
    </style>
@endsection

@section('content')
<div class="container mt-4">
    <div id="form-section" class="form-section">
        <h2 class="section-title">
            <i class="fas fa-plus-circle me-2"></i>
            Add New Record
        </h2>
        <form id="dataForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="firstName" class="form-label">
                        First Name <span class="required-field">*</span>
                    </label>
                    <input type="text" class="form-control" id="firstName" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lastName" class="form-label">
                        Last Name <span class="required-field">*</span>
                    </label>
                    <input type="text" class="form-control" id="lastName" required>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Save Record
                </button>
                <button type="reset" class="btn btn-warning">
                    <i class="fas fa-undo me-2"></i>
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('custom_js')

@endsection


