/*!
 * Admin Dashboard JavaScript
 * Functionality specific to the admin dashboard
 */

// Dashboard specific functionality
window.AdminDashboard = {
    // Initialize dashboard
    init: function() {
        this.initSidebar();
        this.initStats();
        this.initCharts();
        this.initDataTables();
        this.initQuickActions();
        this.initRealTimeUpdates();
    },

    // Initialize sidebar functionality
    initSidebar: function() {
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Set active nav item
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes(currentPage)) {
                link.classList.add('active');
            }
        });
    },

    // Initialize stats animations
    initStats: function() {
        const statsCards = document.querySelectorAll('.stats-card');
        
        // Animate stats on page load
        statsCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });

        // Animate numbers
        const statsNumbers = document.querySelectorAll('.stats-number');
        statsNumbers.forEach(number => {
            this.animateNumber(number);
        });
    },

    // Animate number counting
    animateNumber: function(element) {
        const target = parseInt(element.textContent.replace(/,/g, ''));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 16);
    },

    // Initialize charts
    initCharts: function() {
        // Sales Chart
        const salesChartCtx = document.getElementById('salesChart');
        if (salesChartCtx) {
            this.createSalesChart(salesChartCtx);
        }

        // Orders Chart
        const ordersChartCtx = document.getElementById('ordersChart');
        if (ordersChartCtx) {
            this.createOrdersChart(ordersChartCtx);
        }

        // Revenue Chart
        const revenueChartCtx = document.getElementById('revenueChart');
        if (revenueChartCtx) {
            this.createRevenueChart(revenueChartCtx);
        }
    },

    // Create sales chart
    createSalesChart: function(ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f3f4'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    },

    // Create orders chart
    createOrdersChart: function(ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    // Create revenue chart
    createRevenueChart: function(ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue',
                    data: [1200, 1900, 800, 1500, 2000, 2500, 1800],
                    backgroundColor: '#007bff',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f3f4'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    },

    // Initialize data tables
    initDataTables: function() {
        const tables = document.querySelectorAll('.data-table table');
        
        tables.forEach(table => {
            // Add sorting functionality
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                if (!header.classList.contains('no-sort')) {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sortTable(table, index);
                    });
                }
            });

            // Add row hover effects
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        });
    },

    // Sort table by column
    sortTable: function(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const isAscending = table.getAttribute('data-sort-direction') !== 'asc';
        table.setAttribute('data-sort-direction', isAscending ? 'asc' : 'desc');
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return isAscending ? aValue - bValue : bValue - aValue;
            }
            
            return isAscending ? 
                aValue.localeCompare(bValue) : 
                bValue.localeCompare(aValue);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    },

    // Initialize quick actions
    initQuickActions: function() {
        const quickActionBtns = document.querySelectorAll('.quick-action-btn');
        
        quickActionBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                const action = this.getAttribute('data-action');
                if (action) {
                    AdminDashboard.handleQuickAction(action, e);
                }
            });
        });
    },

    // Handle quick actions
    handleQuickAction: function(action, event) {
        switch (action) {
            case 'add-meal':
                this.showModal('addMealModal');
                break;
            case 'add-category':
                this.showModal('addCategoryModal');
                break;
            case 'view-orders':
                window.location.href = 'orders.php';
                break;
            case 'view-reservations':
                window.location.href = 'reservations.php';
                break;
            default:
                console.log('Unknown action:', action);
        }
    },

    // Show modal
    showModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    },

    // Initialize real-time updates
    initRealTimeUpdates: function() {
        // Update stats every 30 seconds
        setInterval(() => {
            this.updateStats();
        }, 30000);

        // Update notifications every 10 seconds
        setInterval(() => {
            this.updateNotifications();
        }, 10000);
    },

    // Update stats
    updateStats: function() {
        // This would typically fetch from an API
        fetch('api/stats.php')
            .then(response => response.json())
            .then(data => {
                this.updateStatsDisplay(data);
            })
            .catch(error => {
                console.error('Error updating stats:', error);
            });
    },

    // Update stats display
    updateStatsDisplay: function(data) {
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                const currentValue = parseInt(element.textContent.replace(/,/g, ''));
                const newValue = data[key];
                
                if (currentValue !== newValue) {
                    this.animateNumber(element, newValue);
                }
            }
        });
    },

    // Update notifications
    updateNotifications: function() {
        fetch('api/notifications.php')
            .then(response => response.json())
            .then(data => {
                this.updateNotificationBadge(data.count);
                this.updateNotificationList(data.notifications);
            })
            .catch(error => {
                console.error('Error updating notifications:', error);
            });
    },

    // Update notification badge
    updateNotificationBadge: function(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    },

    // Update notification list
    updateNotificationList: function(notifications) {
        const list = document.querySelector('.notification-list');
        if (list) {
            list.innerHTML = '';
            notifications.forEach(notification => {
                const item = document.createElement('div');
                item.className = 'notification-item';
                item.innerHTML = `
                    <div class="notification-content">
                        <h6>${notification.title}</h6>
                        <p>${notification.message}</p>
                        <small>${notification.time}</small>
                    </div>
                `;
                list.appendChild(item);
            });
        }
    },

    // Show loading overlay
    showLoading: function() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    },

    // Hide loading overlay
    hideLoading: function() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
};

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('admin-dashboard')) {
        AdminDashboard.init();
    }
});
