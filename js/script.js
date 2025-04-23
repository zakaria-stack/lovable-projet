// Main JavaScript file for Student Notes application

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile navigation toggle - for responsive design
    const navToggle = document.querySelector('.nav-toggle');
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.classList.toggle('active');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Note content character counter
    const contentTextarea = document.getElementById('content');
    const charCounter = document.getElementById('char-counter');
    
    if (contentTextarea && charCounter) {
        contentTextarea.addEventListener('input', function() {
            const remaining = 10000 - this.value.length;
            charCounter.textContent = `${this.value.length} characters (${remaining} remaining)`;
            
            if (remaining < 0) {
                charCounter.classList.add('text-danger');
            } else {
                charCounter.classList.remove('text-danger');
            }
        });
        
        // Trigger on page load to show initial count
        contentTextarea.dispatchEvent(new Event('input'));
    }
    
    // Password matching check for registration
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatchMessage = document.getElementById('password-match');
    
    if (password && confirmPassword && passwordMatchMessage) {
        function checkPasswordMatch() {
            if (confirmPassword.value === '') {
                passwordMatchMessage.textContent = '';
                return;
            }
            
            if (password.value === confirmPassword.value) {
                passwordMatchMessage.textContent = 'Passwords match';
                passwordMatchMessage.className = 'text-success';
            } else {
                passwordMatchMessage.textContent = 'Passwords do not match';
                passwordMatchMessage.className = 'text-danger';
            }
        }
        
        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
    
    // Module selection change handler
    const moduleSelect = document.getElementById('module_id');
    if (moduleSelect) {
        moduleSelect.addEventListener('change', function() {
            // If this is on the add_note.php page and not part of a form submission
            if (window.location.href.includes('add_note.php') && !document.querySelector('form').classList.contains('submitting')) {
                if (this.value) {
                    // Store in sessionStorage
                    sessionStorage.setItem('selected_module_id', this.value);
                }
            }
        });
        
        // Check for stored module selection
        const storedModuleId = sessionStorage.getItem('selected_module_id');
        if (storedModuleId && !moduleSelect.value) {
            // Try to select the stored module if one isn't already selected
            for (let i = 0; i < moduleSelect.options.length; i++) {
                if (moduleSelect.options[i].value === storedModuleId) {
                    moduleSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }
    
    // Handle form submission to add class for the moduleSelect change handler
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            this.classList.add('submitting');
        });
    });
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(function(tooltip) {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'tooltip';
            tooltipElement.textContent = tooltipText;
            
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = rect.bottom + 10 + 'px';
            tooltipElement.style.left = rect.left + (rect.width / 2) - (tooltipElement.offsetWidth / 2) + 'px';
            tooltipElement.style.opacity = '1';
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = document.querySelector('.tooltip');
            if (tooltipElement) {
                tooltipElement.remove();
            }
        });
    });
});

// Confirmation dialog for delete actions
function confirmDelete(id, type) {
    let message = 'Are you sure you want to delete this item? This action cannot be undone.';
    
    if (type === 'module') {
        message = 'Are you sure you want to delete this module? All notes in this module will also be deleted. This action cannot be undone.';
    } else if (type === 'note') {
        message = 'Are you sure you want to delete this note? This action cannot be undone.';
    } else if (type === 'user') {
        message = 'Are you sure you want to delete this user? All their modules and notes will also be deleted. This action cannot be undone.';
    }
    
    return confirm(message);
}
