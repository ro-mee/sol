// Form validation functions

// Validate required fields
function validateRequired(formId) {
    const form = document.getElementById(formId);
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
            
            // Show error message
            let errorMsg = field.parentNode.querySelector('.error-message');
            if (!errorMsg) {
                errorMsg = document.createElement('span');
                errorMsg.className = 'error-message';
                errorMsg.style.color = '#dc3545';
                errorMsg.style.fontSize = '0.8rem';
                errorMsg.style.display = 'block';
                field.parentNode.appendChild(errorMsg);
            }
            errorMsg.textContent = 'This field is required';
        } else {
            field.classList.remove('error');
            const errorMsg = field.parentNode.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    });
    
    return isValid;
}

// Validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number (basic validation)
function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// Validate number fields
function validateNumber(field, min = null, max = null) {
    const value = parseFloat(field.value);
    
    if (isNaN(value)) {
        field.classList.add('error');
        return false;
    }
    
    if (min !== null && value < min) {
        field.classList.add('error');
        return false;
    }
    
    if (max !== null && value > max) {
        field.classList.add('error');
        return false;
    }
    
    field.classList.remove('error');
    return true;
}

// Clear all validation errors
function clearValidationErrors(formId) {
    const form = document.getElementById(formId);
    const errorFields = form.querySelectorAll('.error');
    const errorMessages = form.querySelectorAll('.error-message');
    
    errorFields.forEach(field => field.classList.remove('error'));
    errorMessages.forEach(msg => msg.remove());
}

// Real-time validation setup
function setupRealTimeValidation(formId) {
    const form = document.getElementById(formId);
    
    // Validate on blur
    form.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
        
        // Remove error on focus
        field.addEventListener('focus', function() {
            this.classList.remove('error');
        });
    });
}

// Password strength validation
function validatePassword(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Character variety checks
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return strength;
}

function getPasswordStrengthText(strength) {
    switch(strength) {
        case 0:
        case 1:
            return { text: 'Very Weak', color: '#dc3545' };
        case 2:
            return { text: 'Weak', color: '#ffc107' };
        case 3:
            return { text: 'Fair', color: '#fd7e14' };
        case 4:
            return { text: 'Good', color: '#20c997' };
        case 5:
        case 6:
            return { text: 'Strong', color: '#28a745' };
        default:
            return { text: 'Very Weak', color: '#dc3545' };
    }
}

// Credit card validation (if needed)
function validateCreditCard(number) {
    const re = /^[0-9]{13,19}$/;
    return re.test(number.replace(/\s/g, ''));
}

// Date validation
function validateDate(dateString, minDate = null, maxDate = null) {
    const date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        return false;
    }
    
    if (minDate) {
        const min = new Date(minDate);
        if (date < min) return false;
    }
    
    if (maxDate) {
        const max = new Date(maxDate);
        if (date > max) return false;
    }
    
    return true;
}

// Form submission with validation
function setupFormValidation(formId, submitCallback) {
    const form = document.getElementById(formId);
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateRequired(formId)) {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            showLoading(submitBtn);
            
            // Call the submit callback
            submitCallback(function(success) {
                hideLoading(submitBtn, originalText);
                if (success) {
                    showNotification('Form submitted successfully!', 'success');
                } else {
                    showNotification('Form submission failed. Please try again.', 'error');
                }
            });
        } else {
            showNotification('Please fill in all required fields correctly.', 'error');
        }
    });
}

// Add CSS for validation errors
const validationStyles = `
    .error {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    input.error:focus, select.error:focus, textarea.error:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
`;

// Add styles to page if not already added
if (!document.querySelector('#validation-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'validation-styles';
    styleSheet.textContent = validationStyles;
    document.head.appendChild(styleSheet);
}
