/*!
 * Home Page JavaScript
 * Functionality specific to the homepage (index.php)
 */

// Home page specific functionality
SushiApp.Home = {
    // Initialize home page
    init: function() {
        this.initHeroAnimations();
        this.initMenuPreview();
        this.initReservationForm();
        this.initCounters();
        this.initTestimonials();
    },

    // Initialize hero section animations
    initHeroAnimations: function() {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        // Parallax effect for hero background
        window.addEventListener('scroll', this.handleHeroParallax.bind(this));

        // Animate hero text on load
        const heroText = document.querySelector('.hero-text');
        if (heroText) {
            setTimeout(() => {
                heroText.classList.add('animate-fade-in');
            }, 500);
        }
    },

    // Handle hero parallax scrolling
    handleHeroParallax: function() {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        hero.style.transform = `translateY(${rate}px)`;
    },

    // Initialize menu preview section
    initMenuPreview: function() {
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach((item, index) => {
            // Add staggered animation
            item.style.animationDelay = `${index * 0.1}s`;
            
            // Add hover effects
            item.addEventListener('mouseenter', this.handleMenuItemHover.bind(this));
            item.addEventListener('mouseleave', this.handleMenuItemLeave.bind(this));
        });

        // Load featured menu items
        this.loadFeaturedItems();
    },

    // Handle menu item hover
    handleMenuItemHover: function(e) {
        const item = e.currentTarget;
        const image = item.querySelector('.menu-item-img');
        
        if (image) {
            image.style.transform = 'scale(1.1)';
        }
    },

    // Handle menu item leave
    handleMenuItemLeave: function(e) {
        const item = e.currentTarget;
        const image = item.querySelector('.menu-item-img');
        
        if (image) {
            image.style.transform = 'scale(1)';
        }
    },

    // Load featured menu items
    loadFeaturedItems: function() {
        // This would typically fetch from an API
        // For now, we'll just add some interactive behavior
        const viewMenuBtn = document.querySelector('.view-menu-btn');
        if (viewMenuBtn) {
            viewMenuBtn.addEventListener('click', function(e) {
                e.preventDefault();
                SushiApp.showAlert('Redirecting to menu...', 'info');
                setTimeout(() => {
                    window.location.href = 'menu.php';
                }, 1000);
            });
        }
    },

    // Initialize reservation form
    initReservationForm: function() {
        const reservationForm = document.getElementById('reservationForm');
        if (!reservationForm) return;

        // Add date picker constraints
        const dateInput = reservationForm.querySelector('input[type="date"]');
        if (dateInput) {
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
            
            // Set maximum date to 3 months from now
            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 3);
            dateInput.setAttribute('max', maxDate.toISOString().split('T')[0]);
        }

        // Add time slot validation
        const timeInput = reservationForm.querySelector('input[type="time"]');
        if (timeInput) {
            timeInput.addEventListener('change', this.validateTimeSlot.bind(this));
        }

        // Add party size validation
        const partySizeInput = reservationForm.querySelector('input[name="party_size"]');
        if (partySizeInput) {
            partySizeInput.addEventListener('change', this.validatePartySize.bind(this));
        }
    },

    // Validate time slot
    validateTimeSlot: function(e) {
        const time = e.target.value;
        const [hours, minutes] = time.split(':').map(Number);
        const timeInMinutes = hours * 60 + minutes;
        
        // Restaurant hours: 11:00 AM - 10:00 PM
        const openTime = 11 * 60; // 11:00 AM
        const closeTime = 22 * 60; // 10:00 PM
        
        if (timeInMinutes < openTime || timeInMinutes > closeTime) {
            SushiApp.showAlert('Please select a time between 11:00 AM and 10:00 PM', 'warning');
            e.target.value = '';
        }
    },

    // Validate party size
    validatePartySize: function(e) {
        const partySize = parseInt(e.target.value);
        
        if (partySize < 1) {
            SushiApp.showAlert('Party size must be at least 1 person', 'warning');
            e.target.value = 1;
        } else if (partySize > 12) {
            SushiApp.showAlert('For parties larger than 12, please call us directly', 'info');
            e.target.value = 12;
        }
    },

    // Initialize counters animation
    initCounters: function() {
        const counters = document.querySelectorAll('.counter');
        
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        counters.forEach(counter => {
            observer.observe(counter);
        });
    },

    // Animate counter
    animateCounter: function(counter) {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 60 FPS
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    },

    // Initialize testimonials carousel
    initTestimonials: function() {
        const testimonialCarousel = document.querySelector('#testimonialCarousel');
        if (!testimonialCarousel) return;

        // Auto-advance testimonials
        setInterval(() => {
            const carousel = bootstrap.Carousel.getInstance(testimonialCarousel);
            if (carousel) {
                carousel.next();
            }
        }, 5000);
    }
};

// Initialize home page when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('home-page')) {
        SushiApp.Home.init();
    }
});

// Add CSS for home page animations
const homeStyle = document.createElement('style');
homeStyle.textContent = `
    .hero-text {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 1s ease, transform 1s ease;
    }
    
    .menu-item {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }
    
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .counter {
        font-size: 2.5rem;
        font-weight: 700;
        color: #dc3545;
    }
    
    .reservation-form {
        transform: translateY(20px);
        opacity: 0;
        animation: slideInUp 0.8s ease forwards;
        animation-delay: 0.3s;
    }
    
    @keyframes slideInUp {
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(homeStyle);
