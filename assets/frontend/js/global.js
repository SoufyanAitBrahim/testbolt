/*!
 * Global Frontend JavaScript
 * Common functionality used across all frontend pages
 */

// Global variables
window.SushiApp = window.SushiApp || {};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    SushiApp.init();
});

// Main application object
SushiApp = {
    // Initialize the application
    init: function() {
        this.initBootstrap();
        this.initSmoothScrolling();
        this.initFormHandlers();
        this.initTooltips();
        this.initAnimations();
    },

    // Initialize Bootstrap components
    initBootstrap: function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    },

    // Initialize smooth scrolling for anchor links
    initSmoothScrolling: function() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    },

    // Initialize form handlers
    initFormHandlers: function() {
        // Handle reservation form
        const reservationForm = document.getElementById('reservationForm');
        if (reservationForm) {
            reservationForm.addEventListener('submit', this.handleReservationForm.bind(this));
        }

        // Handle contact form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', this.handleContactForm.bind(this));
        }

        // Handle newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', this.handleNewsletterForm.bind(this));
        }
    },

    // Initialize tooltips
    initTooltips: function() {
        // Add tooltips to buttons and interactive elements
        const elements = document.querySelectorAll('.btn, .nav-link, .card');
        elements.forEach(element => {
            if (!element.hasAttribute('data-bs-toggle')) {
                const title = element.getAttribute('title') || element.getAttribute('aria-label');
                if (title) {
                    element.setAttribute('data-bs-toggle', 'tooltip');
                    element.setAttribute('data-bs-placement', 'top');
                    element.setAttribute('title', title);
                }
            }
        });
    },

    // Initialize animations
    initAnimations: function() {
        // Fade in elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);

        // Observe elements with animation class
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    },

    // Handle reservation form submission
    handleReservationForm: function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        submitBtn.disabled = true;

        // Simulate API call (replace with actual endpoint)
        setTimeout(() => {
            this.showAlert('Reservation request submitted! We will contact you shortly.', 'success');
            form.reset();
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 1500);
    },

    // Handle contact form submission
    handleContactForm: function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;

        // Simulate API call (replace with actual endpoint)
        setTimeout(() => {
            this.showAlert('Thank you for your message! We will get back to you soon.', 'success');
            form.reset();
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 1500);
    },

    // Handle newsletter form submission
    handleNewsletterForm: function(e) {
        e.preventDefault();
        const form = e.target;
        const email = form.querySelector('input[type="email"]').value;

        if (this.validateEmail(email)) {
            this.showAlert('Thank you for subscribing to our newsletter!', 'success');
            form.reset();
        } else {
            this.showAlert('Please enter a valid email address.', 'danger');
        }
    },

    // Validate email address
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Show alert message
    showAlert: function(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-dynamic');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-dynamic`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Insert alert at the top of the page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    },

    // Show loading spinner
    showLoading: function(element) {
        element.classList.add('loading');
    },

    // Hide loading spinner
    hideLoading: function(element) {
        element.classList.remove('loading');
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Debounce function for performance
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
};

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    
    .animate-fade-in {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .loading {
        position: relative;
        pointer-events: none;
    }
    
    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #dc3545;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 1000;
    }
    
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
`;
document.head.appendChild(style);
