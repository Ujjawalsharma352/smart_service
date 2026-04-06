// Smart Service Finder - Main JavaScript File

// ===== UTILITY FUNCTIONS =====
function $(selector) {
    return document.querySelector(selector);
}

function $$(selector) {
    return document.querySelectorAll(selector);
}

function showElement(element) {
    element.style.display = 'block';
    element.classList.add('fade-in');
}

function hideElement(element) {
    element.style.display = 'none';
}

function toggleElement(element) {
    if (element.style.display === 'none') {
        showElement(element);
    } else {
        hideElement(element);
    }
}

// ===== FORM VALIDATION =====
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.length >= 10;
}

function validateForm(formId) {
    const form = $(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showError(input, 'This field is required');
            isValid = false;
        } else {
            clearError(input);
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            if (!validateEmail(input.value)) {
                showError(input, 'Please enter a valid email address');
                isValid = false;
            }
        }
        
        // Phone validation
        if (input.type === 'tel' && input.value) {
            if (!validatePhone(input.value)) {
                showError(input, 'Please enter a valid phone number');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showError(input, message) {
    clearError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.color = 'var(--error-color)';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    
    input.style.borderColor = 'var(--error-color)';
    input.parentNode.insertBefore(errorDiv, input.nextSibling);
}

function clearError(input) {
    input.style.borderColor = '';
    const errorMessage = input.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

// ===== AJAX FUNCTIONS =====
function ajaxRequest(method, url, data, callback) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(response, null);
                } catch (e) {
                    callback(xhr.responseText, null);
                }
            } else {
                callback(null, xhr.status);
            }
        }
    };
    
    xhr.onerror = function() {
        callback(null, 'Network error');
    };
    
    if (method === 'POST' && data) {
        xhr.send(serializeData(data));
    } else {
        xhr.send();
    }
}

function serializeData(data) {
    return Object.keys(data)
        .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
        .join('&');
}

// ===== NOTIFICATION SYSTEM =====
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '400px';
    notification.style.boxShadow = 'var(--shadow-lg)';
    notification.style.cursor = 'pointer';
    
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            notification.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, duration);
    
    // Click to dismiss
    notification.addEventListener('click', () => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

function getAppBaseUrl() {
    const parts = window.location.pathname.split('/').filter(Boolean);
    const appIndex = parts.indexOf('smart_service');
    return appIndex >= 0 ? '/' + parts.slice(0, appIndex + 1).join('/') : '';
}

function getLanguageHandlerUrl(lang) {
    return getAppBaseUrl() + '/notifications/language_helper.php?lang=' + encodeURIComponent(lang);
}

function changeLanguage(lang) {
    window.location.href = getLanguageHandlerUrl(lang);
}

function initFloatingLanguageSwitcher() {
    if (document.getElementById('global-language-switcher')) return;

    const widget = document.createElement('div');
    widget.id = 'global-language-switcher';
    widget.innerHTML = `
        <div class="language-switcher-widget">
            <button type="button" class="language-switcher-button">🌐</button>
            <div class="language-switcher-menu">
                <a href="${getLanguageHandlerUrl('en')}">English</a>
                <a href="${getLanguageHandlerUrl('hi')}">हिन्दी</a>
                <a href="${getLanguageHandlerUrl('fr')}">Français</a>
            </div>
        </div>
    `;
    document.body.appendChild(widget);

    widget.querySelector('.language-switcher-button').addEventListener('click', (event) => {
        event.stopPropagation();
        widget.classList.toggle('open');
    });

    document.addEventListener('click', () => {
        widget.classList.remove('open');
    });
}

// ===== SEARCH FUNCTIONALITY =====
function initSearch() {
    const searchInput = $('#searchInput');
    const searchResults = $('#searchResults');
    
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            if (searchResults) hideElement(searchResults);
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
}

function performSearch(query) {
    const searchResults = $('#searchResults');
    
    // Show loading
    if (searchResults) {
        searchResults.innerHTML = '<div class="loading"></div>';
        showElement(searchResults);
    }
    
    // Perform AJAX search
    ajaxRequest('GET', `../api/search.php?q=${encodeURIComponent(query)}`, null, function(response, error) {
        if (error) {
            console.error('Search error:', error);
            if (searchResults) hideElement(searchResults);
            return;
        }
        
        displaySearchResults(response);
    });
}

function displaySearchResults(results) {
    const searchResults = $('#searchResults');
    
    if (!searchResults) return;
    
    if (!results || results.length === 0) {
        searchResults.innerHTML = '<p class="text-center text-secondary">No results found</p>';
        return;
    }
    
    let html = '';
    results.forEach(result => {
        html += `
            <div class="search-result-item p-4 border-bottom">
                <h4><a href="../user/service_detail.php?id=${result.id}">${result.title}</a></h4>
                <p class="text-secondary">${result.description.substring(0, 100)}...</p>
                <div class="flex justify-between items-center mt-2">
                    <span class="service-category">${result.category}</span>
                    <span class="service-price">$${result.price}</span>
                </div>
            </div>
        `;
    });
    
    searchResults.innerHTML = html;
    showElement(searchResults);
}

// ===== RATING SYSTEM =====
function initRating() {
    const ratingInputs = $$('.rating-input');
    
    ratingInputs.forEach(input => {
        const stars = input.parentNode.querySelectorAll('.star');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                input.value = index + 1;
                updateStars(stars, index + 1);
            });
            
            star.addEventListener('mouseenter', () => {
                updateStars(stars, index + 1);
            });
        });
        
        input.parentNode.addEventListener('mouseleave', () => {
            updateStars(stars, input.value || 0);
        });
        
        // Initialize
        updateStars(stars, input.value || 0);
    });
}

function updateStars(stars, rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('empty');
            star.textContent = '★';
        } else {
            star.classList.add('empty');
            star.textContent = '☆';
        }
    });
}

// ===== BOOKING SYSTEM =====
function initBooking() {
    const bookingForm = $('#bookingForm');
    
    if (!bookingForm) return;
    
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm('bookingForm')) {
            return;
        }
        
        const formData = new FormData(this);
        const data = {};
        
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Processing...';
        
        // Submit booking
        ajaxRequest('POST', '../api/book_service.php', data, function(response, error) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            
            if (error) {
                showNotification('Booking failed. Please try again.', 'error');
                return;
            }
            
            if (response.success) {
                showNotification('Service booked successfully!', 'success');
                bookingForm.reset();
                
                // Redirect to bookings page after delay
                setTimeout(() => {
                    window.location.href = '../user/my_bookings.php';
                }, 2000);
            } else {
                showNotification(response.message || 'Booking failed', 'error');
            }
        });
    });
}

// ===== SIDEBAR TOGGLE (MOBILE) =====
function initSidebar() {
    const sidebarToggle = $('#sidebarToggle');
    const sidebar = $('.sidebar');
    
    if (!sidebarToggle || !sidebar) return;
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// ===== DATE PICKER =====
function initDatePicker() {
    const dateInputs = $$('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        input.min = today;
        
        // Prevent past dates
        input.addEventListener('change', function() {
            if (this.value < today) {
                this.value = today;
                showNotification('Please select a future date', 'warning');
            }
        });
    });
}

// ===== IMAGE PREVIEW =====
function initImagePreview() {
    const imageInputs = $$('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Image size must be less than 5MB', 'error');
                this.value = '';
                return;
            }
            
            // Check file type
            if (!file.type.startsWith('image/')) {
                showNotification('Please select a valid image file', 'error');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = input.parentNode.querySelector('.image-preview');
                if (preview) {
                    preview.src = e.target.result;
                    showElement(preview);
                }
            };
            reader.readAsDataURL(file);
        });
    });
}

// ===== CONFIRMATION DIALOG =====
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ===== PRINT FUNCTIONALITY =====
function initPrint() {
    const printButtons = $$('[data-print]');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            const printTarget = this.getAttribute('data-print');
            const element = printTarget ? $(printTarget) : document.body;
            
            if (element) {
                window.print();
            }
        });
    });
}

// ===== COPY TO CLIPBOARD =====
function copyToClipboard(text, message = 'Copied to clipboard!') {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification(message, 'success', 2000);
        }).catch(err => {
            console.error('Copy failed:', err);
            fallbackCopy(text, message);
        });
    } else {
        fallbackCopy(text, message);
    }
}

function fallbackCopy(text, message) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification(message, 'success', 2000);
    } catch (err) {
        console.error('Copy failed:', err);
        showNotification('Copy failed', 'error');
    }
    
    document.body.removeChild(textarea);
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initSearch();
    initRating();
    initBooking();
    initSidebar();
    initDatePicker();
    initImagePreview();
    initPrint();
    initFloatingLanguageSwitcher();
    
    // Auto-hide alerts after 5 seconds
    const alerts = $$('.alert:not(.notification)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, 5000);
    });
    
    // Smooth scroll for anchor links
    const anchorLinks = $$('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Form validation on submit - Only validate forms that explicitly want validation
    const forms = $$('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Only validate if form has an ID and is not booking/service form
            if (this.id && this.id !== 'serviceForm' && this.id !== 'bookingForm') {
                if (!validateForm(this.id || 'form')) {
                    e.preventDefault();
                    showNotification('Please fix the errors in the form', 'error');
                }
            }
        });
    });
    
    // Ensure booking forms submit without any interference
    const bookingForm = $('#bookingForm');
    if (bookingForm) {
        // Remove any existing event listeners that might block submission
        bookingForm.addEventListener('submit', function(e) {
            console.log('Booking form submitting normally - no interference');
            // Let the form submit naturally without any prevention
        });
    }
    
    const serviceForm = $('#serviceForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            console.log('Service form submitting normally - no interference');
            // Let the form submit naturally without any prevention
        });
    }
    
    console.log('Smart Service Finder - JavaScript initialized');
});

// ===== GLOBAL ERROR HANDLING =====
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    showNotification('An unexpected error occurred. Please refresh the page.', 'error');
});

// ===== PERFORMANCE MONITORING =====
if (window.performance && window.performance.navigation) {
    const navigationType = window.performance.navigation.type;
    if (navigationType === 1) {
        console.log('Page reloaded');
    }
}
