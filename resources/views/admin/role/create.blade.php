@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Add New Role</h4>
                        <p class="text-muted mb-0">Create a new role</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role.index') }}">Roles and Permissions</a></li>
                            <li class="breadcrumb-item active">Add Product</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('role.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                            @csrf
                            
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <label for="name" class="form-label">{{ __('Role Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('role') is-invalid @enderror" 
                                       name="role" id="name" placeholder="Enter role name" 
                                       value="{{ old('role') }}" required>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4">

                            <!-- Permissions Section -->
                            @foreach($permissions as $groupName => $permission)
                            <div class="mb-4">
                                <h6 class="mb-3">{{ $groupName }}</h6>
                                <div class="d-flex flex-wrap gap-4">
                                    @foreach ($permission as $index => $item)
                                    <div class="col-md-2">
                                        <input type="checkbox" 
                                               value="{{ $item->name }}" 
                                               name="permissions[]" 
                                               class="btn-check" 
                                               id="permission-{{ $groupName }}-{{ $index }}"
                                               autocomplete="off">
                                        <label class="btn btn-outline-primary btn-sm" 
                                               for="permission-{{ $groupName }}-{{ $index }}">
                                            {{ $item->name }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-4">
                            @endforeach

                            

                            <!-- Form Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Create Role
                                </button>
                                <a href="{{ route('role.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Help -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Permission Guide</h6>
                        <div class="text-muted small">
                            <p><strong>Index:</strong> View list of items</p>
                            <p><strong>Create:</strong> Add new items</p>
                            <p><strong>Edit:</strong> Modify existing items</p>
                            <p><strong>Delete:</strong> Remove items</p>
                            <p><strong>Approve:</strong> Approve pending items</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Tips</h6>
                        <ul class="text-muted small">
                            <li>Use descriptive role names</li>
                            <li>Grant minimal required permissions</li>
                            <li>Review permissions regularly</li>
                            <li>Test roles before assigning to users</li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAll()">
                                Select All
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="selectNone()">
                                Select None
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom CSS for Permission Grid Layout */
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

/* Ensure switches are properly aligned */
.form-check.form-switch {
    padding-left: 2.5em;
    min-height: 1.5rem;
}

.form-check.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .permission-grid {
        grid-template-columns: 1fr;
    }
    
    .d-flex.flex-wrap.gap-4 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
}

/* Hover effects for better UX */
.form-check.form-switch:hover {
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.375rem;
    padding: 0.25rem;
    margin: -0.25rem;
}

/* Button checkbox styling */
.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}
</style>

<script>
// JavaScript functions for quick selection
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        // Trigger change event to update button styling
        checkbox.dispatchEvent(new Event('change'));
    });
}

function selectNone() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        // Trigger change event to update button styling
        checkbox.dispatchEvent(new Event('change'));
    });
}

function selectCategory(category) {
    const checkboxes = document.querySelectorAll(`input[id*="${category}"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
        checkbox.dispatchEvent(new Event('change'));
    });
}

// Form validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const nameInput = document.getElementById('name');
    const checkedPermissions = document.querySelectorAll('input[name="permissions[]"]:checked');
    
    if (nameInput.value.trim() === '') {
        e.preventDefault();
        alert('Please enter a role name');
        nameInput.focus();
        return;
    }
    
    if (checkedPermissions.length === 0) {
        e.preventDefault();
        alert('Please select at least one permission');
        return;
    }
});

// Auto-save draft functionality (optional)
function saveDraft() {
    const formData = new FormData(document.getElementById('productForm'));
    const draft = {
        name: formData.get('name'),
        permissions: formData.getAll('permissions[]'),
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('role_draft', JSON.stringify(draft));
}

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('role_draft');
    if (draft) {
        const data = JSON.parse(draft);
        document.getElementById('name').value = data.name || '';
        
        data.permissions?.forEach(permission => {
            const checkbox = document.querySelector(`input[value="${permission}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Auto-save every 30 seconds
    setInterval(saveDraft, 30000);
});
</script>

@endsection