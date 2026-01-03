@extends('Website.Layout.master')

@section('custom_css')
<style>
    .search-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .search-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .search-header h2 {
        color: #333;
        font-weight: 700;
        font-size: 28px;
    }

    .doc-type-selector {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .radio-card {
        flex: 1;
    }

    .radio-card input[type="radio"] {
        display: none;
    }

    .radio-card label {
        display: block;
        padding: 30px 20px;
        border: 3px solid #ddd;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }

    .radio-card label:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }

    .radio-card input[type="radio"]:checked + label {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .radio-card i {
        font-size: 40px;
        display: block;
        margin-bottom: 10px;
    }

    .dob-field {
        display: none;
    }

    .dob-field.show {
        display: block;
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="search-container">
        <div class="search-header">
            <h2><i class="fas fa-search me-2"></i>Document Verification</h2>
            <p class="text-muted">Verify RC or DL using Government Database (SurePass)</p>
        </div>

        {{-- ✅ Error Alert --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Error:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ✅ Success Alert --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ✅ Validation Errors --}}
        @if($errors->any())
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('document-verification.search') }}" id="verificationForm">
            @csrf

            {{-- Document Type Radio Buttons --}}
            <div class="doc-type-selector">
                <div class="radio-card">
                    <input type="radio"
                           id="rc_radio"
                           name="document_type"
                           value="rc"
                           {{ old('document_type', 'rc') == 'rc' ? 'checked' : '' }}>
                    <label for="rc_radio">
                        <i class="fas fa-car"></i>
                        <div><strong>RC Verification</strong></div>
                        <small>Registration Certificate</small>
                    </label>
                </div>
                <div class="radio-card">
                    <input type="radio"
                           id="dl_radio"
                           name="document_type"
                           value="dl"
                           {{ old('document_type') == 'dl' ? 'checked' : '' }}>
                    <label for="dl_radio">
                        <i class="fas fa-id-card"></i>
                        <div><strong>DL Verification</strong></div>
                        <small>Driving License</small>
                    </label>
                </div>
            </div>

            {{-- ID Number Input --}}
            <div class="mb-3">
                <label class="form-label" id="id_label">
                    RC Number <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="id_number"
                       id="id_number"
                       class="form-control form-control-lg @error('id_number') is-invalid @enderror"
                       placeholder="Enter RC Number (e.g., RJ12XJ1234)"
                       value="{{ old('id_number') }}"
                       required>
                @error('id_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted" id="help_text">
                    <i class="fas fa-info-circle"></i> Example RC: RJ12XJ1234
                </small>
            </div>

            {{-- DOB Input (for DL only) - ✅ FIXED --}}
            <div class="mb-3 dob-field {{ old('document_type') == 'dl' ? 'show' : '' }}" id="dob_field">
                <label class="form-label">
                    Date of Birth <span class="text-danger">*</span>
                </label>
                <input type="date"
                       name="dob"
                       id="dob_input"
                       class="form-control form-control-lg @error('dob') is-invalid @enderror"
                       value="{{ old('dob') }}"
                       max="{{ date('Y-m-d') }}"
                       {{ old('document_type', 'rc') == 'rc' ? 'disabled' : '' }}>
                @error('dob')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-search me-2"></i>Verify Document
            </button>
        </form>

        {{-- ✅ Debug Info (Remove in production) --}}
        @if(config('app.debug') && session('debug'))
            <div class="alert alert-info mt-3">
                <strong><i class="fas fa-bug me-2"></i>Debug Info:</strong>
                <pre class="mb-0 mt-2" style="max-height: 200px; overflow-y: auto;">{{ print_r(session('debug'), true) }}</pre>
            </div>
        @endif
    </div>
</div>
@endsection

@section('custom_js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rcRadio = document.getElementById('rc_radio');
    const dlRadio = document.getElementById('dl_radio');
    const dobField = document.getElementById('dob_field');
    const idLabel = document.getElementById('id_label');
    const idInput = document.getElementById('id_number');
    const dobInput = document.getElementById('dob_input');
    const helpText = document.getElementById('help_text');

    function toggleFields() {
        if (dlRadio.checked) {
            // ✅ DL Mode
            dobField.classList.add('show');
            idLabel.innerHTML = 'DL Number <span class="text-danger">*</span>';
            idInput.placeholder = 'Enter DL Number (e.g., TS02620190003657)';

            // ✅ CRITICAL: Enable DOB field
            dobInput.removeAttribute('disabled');
            dobInput.setAttribute('required', 'required');

            helpText.innerHTML = '<i class="fas fa-info-circle"></i> Example DL: TS02620190003657';
        } else {
            // ✅ RC Mode
            dobField.classList.remove('show');
            idLabel.innerHTML = 'RC Number <span class="text-danger">*</span>';
            idInput.placeholder = 'Enter RC Number (e.g., RJ12XJ1234)';

            // ✅ CRITICAL: Disable DOB field completely
            dobInput.setAttribute('disabled', 'disabled');
            dobInput.removeAttribute('required');
            dobInput.value = ''; // Clear value

            helpText.innerHTML = '<i class="fas fa-info-circle"></i> Example RC: RJ12XJ1234';
        }
    }

    rcRadio.addEventListener('change', toggleFields);
    dlRadio.addEventListener('change', toggleFields);

    // ✅ Initialize on page load
    toggleFields();
});
</script>
@endsection
