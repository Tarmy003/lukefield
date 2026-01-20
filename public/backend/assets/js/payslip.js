$(document).ready(function() {
    let selectedPayslipId = null;
    
    // Preview payslip
    $(document).on('click', '.preview-payslip', function() {
        const payslipId = $(this).data('payslip-id');
        
        $('#previewModal').modal('show');
        $('#previewContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading payslip details...</p>
            </div>
        `);
        
        // Construct URL properly
        const previewUrl = '{{ url("profile/payslips/preview") }}/' + payslipId;
        
        // Fetch payslip preview data
        $.get(previewUrl)
            .done(function(response) {
                if (response.success) {
                    const payslip = response.payslip;
                    let extraEarningsHtml = '';
                    let extraDeductionsHtml = '';
                    
                    if (payslip.extra_earnings && payslip.extra_earnings.length > 0) {
                        extraEarningsHtml = payslip.extra_earnings.map(item => 
                            `<div class="d-flex justify-content-between">
                                <span>${item.name}</span>
                                <span>₦${item.amount}</span>
                            </div>`
                        ).join('');
                    }
                    
                    if (payslip.extra_deductions && payslip.extra_deductions.length > 0) {
                        extraDeductionsHtml = payslip.extra_deductions.map(item => 
                            `<div class="d-flex justify-content-between">
                                <span>${item.name}</span>
                                <span>₦${item.amount}</span>
                            </div>`
                        ).join('');
                    }
                    
                    $('#previewContent').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-semibold">Payslip Information</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Period:</td>
                                            <td><strong>${payslip.payslip_month}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Payment Date:</td>
                                            <td>${payslip.payment_date}</td>
                                            </tr>
                                        <tr>
                                            <td>Status:</td>
                                            <td><span class="badge bg-success">${payslip.status}</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-semibold">Salary Breakdown</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Basic Salary:</td>
                                            <td><strong>₦${payslip.basic_salary}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Gross Pay:</td>
                                            <td><strong class="text-success">₦${payslip.gross_pay}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Deductions:</td>
                                            <td><strong class="text-danger">₦${payslip.total_deductions}</strong></td>
                                        </tr>
                                        <tr class="table-light">
                                            <td><strong>Net Pay:</strong></td>
                                            <td><strong class="text-primary fs-6">₦${payslip.net_pay}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        ${extraEarningsHtml ? `
                            <div class="mt-3">
                                <h6 class="fw-semibold text-success">
                                    <i class="fas fa-plus-circle me-1"></i>Additional Earnings
                                </h6>
                                <div class="card border-success">
                                    <div class="card-body py-2">
                                        ${extraEarningsHtml}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${extraDeductionsHtml ? `
                            <div class="mt-3">
                                <h6 class="fw-semibold text-danger">
                                    <i class="fas fa-minus-circle me-1"></i>Additional Deductions
                                </h6>
                                <div class="card border-danger">
                                    <div class="card-body py-2">
                                        ${extraDeductionsHtml}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    `);
                } else {
                    $('#previewContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            ${response.message || 'Error loading payslip preview'}
                        </div>
                    `);
                }
            })
            .fail(function(xhr) {
                let errorMessage = 'Error loading payslip preview';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#previewContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${errorMessage}
                    </div>
                `);
            });
    });
    
    // Download payslip - show password modal
    $(document).on('click', '.download-payslip', function() {
        selectedPayslipId = $(this).data('payslip-id');
        $('#selectedPayslipId').val(selectedPayslipId);
        $('#verificationPassword').val('');
        $('#passwordError').text('');
        $('#verificationPassword').removeClass('is-invalid');
        $('#passwordModal').modal('show');
        
        // Focus on password field when modal is shown
        $('#passwordModal').on('shown.bs.modal', function() {
            $('#verificationPassword').focus();
        });
    });
    
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#verificationPassword');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Password form submission
    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        
        const password = $('#verificationPassword').val();
        const payslipId = $('#selectedPayslipId').val();
        const submitBtn = $('#verifyButton');
        const originalText = submitBtn.html();
        
        if (!password) {
            $('#verificationPassword').addClass('is-invalid');
            $('#passwordError').text('Password is required');
            return;
        }
        
        // Reset validation state
        $('#verificationPassword').removeClass('is-invalid');
        $('#passwordError').text('');
        
        // Show loading state
        submitBtn.prop('disabled', true).html(`
            <div class="spinner-border spinner-border-sm me-1" role="status"></div>
            Verifying...
        `);
        
        $.ajax({
            url: '{{ route("user.payslip.verify-download") }}',
            method: 'POST',
            data: {
                password: password,
                payslip_id: payslipId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#passwordModal').modal('hide');
                    
                    // Show success message
                    toastr.success(response.message || 'Password verified! Download starting...');
                    
                    // Start download
                    if (response.download_url) {
                        window.location.href = response.download_url;
                    }
                    
                    // Reset form
                    $('#passwordForm')[0].reset();
                } else {
                    // Handle logout case
                    if (response.logout) {
                        toastr.error(response.message);
                        setTimeout(() => {
                            window.location.href = '{{ route("login") }}';
                        }, 2000);
                    } else {
                        $('#verificationPassword').addClass('is-invalid');
                        $('#passwordError').text(response.message);
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422 && xhr.responseJSON.errors) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    if (errors.password) {
                        errorMessage = errors.password[0];
                    } else if (errors.payslip_id) {
                        errorMessage = errors.payslip_id[0];
                    }
                }
                
                $('#verificationPassword').addClass('is-invalid');
                $('#passwordError').text(errorMessage);
                
                if (xhr.status === 401) {
                    // Handle logout case
                    toastr.error('Session expired. Please login again.');
                    setTimeout(() => {
                        window.location.href = '{{ route("login") }}';
                    }, 2000);
                }
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Download latest payslip
    window.downloadLatest = function() {
        const latestPayslipId = window.latestPayslipId; // Pass this value from Blade to JavaScript
        if (latestPayslipId) {
            $('.download-payslip[data-payslip-id="' + latestPayslipId + '"]').trigger('click');
        } else {
            toastr.warning('No payslips available to download');
        }
    }
    
    // Download all payslips (placeholder - implement based on your requirements)
    window.downloadAll = function() {
        toastr.info('Bulk download feature coming soon!');
        // You can implement bulk download logic here
        // This might involve creating a ZIP file on the server
    }
    
    // Clear password when modal is hidden
    $('#passwordModal').on('hidden.bs.modal', function() {
        $('#passwordForm')[0].reset();
        $('#verificationPassword').removeClass('is-invalid');
        $('#passwordError').text('');
        selectedPayslipId = null;
    });
    
    // Allow Enter key to submit password form
    $('#verificationPassword').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#passwordForm').submit();
        }
    });
    
    // Add tooltips to action buttons
    $('[title]').tooltip();
    
    // Auto-hide alerts after 5 seconds
    $('.alert').not('.alert-permanent').delay(5000).fadeOut('slow');
});


// Display toastr notifications
if (sessionMessages.success) {
    toastr.success(sessionMessages.success);
}
if (sessionMessages.error) {
    toastr.error(sessionMessages.error);
}
if (sessionMessages.warning) {
    toastr.warning(sessionMessages.warning);
}
if (sessionMessages.errors && sessionMessages.errors.length > 0) {
    sessionMessages.errors.forEach(error => toastr.error(error));
}

// Global function to show success/error messages
window.showAlert = function(type, title, text, timer = 3000) {
    Swal.fire({
        icon: type,
        title: title,
        text: text,
        timer: timer,
        showConfirmButton: timer > 3000,
        toast: timer <= 3000,
        position: timer <= 3000 ? 'top-end' : 'center'
    });
};

// Function to show confirmation dialogs
window.confirmAction = function(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
};

// Function to show loading overlay
window.showLoading = function(text = 'Processing...') {
    Swal.fire({
        title: text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
};

// Function to close loading overlay
window.hideLoading = function() {
    Swal.close();
};

// Enhanced payslip download with better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltips to download buttons
    const downloadButtons = document.querySelectorAll('.download-payslip-btn');
    downloadButtons.forEach(button => {
        button.setAttribute('title', 'Download payslip (password required)');
        button.setAttribute('data-bs-toggle', 'tooltip');
    });

    // Initialize Bootstrap tooltips
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Add loading states to buttons
    function setButtonLoading(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-download me-1"></i>Download';
        }
    }

    // Enhanced password input with strength indicator
    const passwordInput = document.getElementById('password-input');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            // Clear any previous error messages
            document.getElementById('password-error').style.display = 'none';
            document.getElementById('attempts-warning').style.display = 'none';
        });

        // Add caps lock warning
        passwordInput.addEventListener('keydown', function(e) {
            const capsLockWarning = document.getElementById('caps-lock-warning') || 
                                   createCapsLockWarning();
            
            if (e.getModifierState('CapsLock')) {
                capsLockWarning.style.display = 'block';
            } else {
                capsLockWarning.style.display = 'none';
            }
        });
    }

    function createCapsLockWarning() {
        const warning = document.createElement('div');
        warning.id = 'caps-lock-warning';
        warning.className = 'text-warning small mt-1';
        warning.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Caps Lock is on';
        warning.style.display = 'none';
        passwordInput.parentNode.parentNode.appendChild(warning);
        return warning;
    }
});

// Network status monitoring
window.addEventListener('online', function() {
    showAlert('success', 'Connected', 'Internet connection restored', 2000);
});

window.addEventListener('offline', function() {
    showAlert('error', 'Disconnected', 'Internet connection lost', 0);
});

// Auto-refresh payslips list every 5 minutes (if on payslips page)
if (window.location.href.includes('payslips') || document.getElementById('payslips')) {
    setInterval(function() {
        // Only refresh if user is active (not idle)
        if (document.hasFocus()) {
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    console.log('Payslips data refreshed');
                }
            })
            .catch(error => {
                console.log('Auto-refresh failed:', error);
            });
        }
    }, 300000); // 5 minutes
}
