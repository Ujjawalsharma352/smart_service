// Smart Service Finder - Notification System

class NotificationSystem {
    constructor() {
        this.userId = document.querySelector('meta[name="user-id"]')?.content;
        this.userRole = document.querySelector('meta[name="user-role"]')?.content;
        this.bell = document.getElementById('notification-bell');
        this.badge = document.getElementById('notification-badge');
        this.dropdown = document.getElementById('notification-dropdown');
        this.list = document.getElementById('notification-list');
        this.closeButton = document.getElementById('notification-close');
        
        this.notifications = [];
        this.unreadCount = 0;
        this.lastNotificationIds = new Set();
        this.pollingInterval = null;
        this.apiUrl = '/smart_service/notifications/notification_api.php';
        
        this.init();
    }
    
    init() {
        if (!this.bell || !this.userId) return;
        
        // Initialize event listeners
        this.setupEventListeners();
        
        // Start polling for notifications
        this.startPolling();
        
        // Load initial notifications
        this.loadNotifications();
    }
    
    setupEventListeners() {
        // Bell click toggle
        this.bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && !this.bell.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        if (this.dropdown) {
            this.dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        if (this.closeButton) {
            this.closeButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.closeDropdown();
            });
        }
    }
    
    toggleDropdown() {
        if (this.dropdown.style.display === 'block') {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        this.dropdown.style.display = 'block';
        this.dropdown.classList.add('show');
        this.loadNotifications();
    }
    
    closeDropdown() {
        this.dropdown.style.display = 'none';
        this.dropdown.classList.remove('show');
    }
    
    async loadNotifications() {
        try {
            const response = await fetch(`${this.apiUrl}?action=fetch&user_id=${this.userId}&role=${this.userRole}`);
            const data = await response.json();
            
            if (data.success) {
                const previousIds = new Set(this.notifications.map(n => n.id));
                const newNotifications = (data.notifications || []).filter(n => !previousIds.has(n.id) && !n.is_read);
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.renderNotifications();
                this.updateBadge();

                if (newNotifications.length > 0) {
                    newNotifications.slice(0, 2).forEach(notification => {
                        this.showToast(notification.message, notification.type || 'info');
                    });
                }
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError('Unable to load notifications');
        }
    }
    
    renderNotifications() {
        if (!this.list) return;
        
        if (this.notifications.length === 0) {
            const noNotificationsText = window.lang?.no_notifications || 'No notifications';
            this.list.innerHTML = `
                <div class="no-notifications">
                    <p>${noNotificationsText}</p>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
            </div>
        `).join('');
        
        this.list.innerHTML = html;
    }
    
    updateBadge() {
        if (!this.badge) return;
        
        if (this.unreadCount > 0) {
            this.badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            this.badge.style.display = 'block';
            this.badge.classList.add('pulse');
        } else {
            this.badge.style.display = 'none';
            this.badge.classList.remove('pulse');
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=mark_read&notification_id=${notificationId}`);
            const data = await response.json();
            
            if (data.success) {
                // Update local notification
                const notification = this.notifications.find(n => n.id == notificationId);
                if (notification) {
                    notification.is_read = 1;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.renderNotifications();
                    this.updateBadge();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markNotificationsAsRead() {
        const unreadNotifications = this.notifications.filter(n => !n.is_read);
        
        if (unreadNotifications.length === 0) return;
        
        try {
            const response = await fetch(`${this.apiUrl}?action=mark_all_read&user_id=${this.userId}&role=${this.userRole}`);
            const data = await response.json();
            
            if (data.success) {
                // Update local notifications
                this.notifications.forEach(n => n.is_read = 1);
                this.unreadCount = 0;
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.error('Error marking notifications as read:', error);
        }
    }
    
    async markAllAsRead() {
        await this.markNotificationsAsRead();
        this.showToast('All notifications marked as read', 'success');
    }
    
    async deleteNotification(notificationId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=delete&notification_id=${notificationId}&user_id=${this.userId}`);
            const data = await response.json();
            
            if (data.success) {
                // Remove from local notifications
                const index = this.notifications.findIndex(n => n.id == notificationId);
                if (index > -1) {
                    const notification = this.notifications[index];
                    if (!notification.is_read) {
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                    }
                    this.notifications.splice(index, 1);
                    this.renderNotifications();
                    this.updateBadge();
                }
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }
    
    showToast(message, type = 'info') {
        // Use the existing showNotification function from main.js
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        }
    }
    
    showError(message) {
        if (this.list) {
            this.list.innerHTML = `
                <div class="notification-error">
                    <p>${message}</p>
                    <button onclick="location.reload()">Retry</button>
                </div>
            `;
        }
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        
        return date.toLocaleDateString();
    }
    
    startPolling() {
        // Poll for new notifications every 30 seconds
        this.pollingInterval = setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }
    
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    // Public method to create a notification (for booking status changes)
    async createNotification(message, type = 'info', relatedId = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('user_id', this.userId);
            formData.append('user_role', this.userRole);
            formData.append('message', message);
            formData.append('type', type);
            if (relatedId) {
                formData.append('related_id', relatedId);
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload notifications to show the new one
                this.loadNotifications();
                this.showToast(message, type);
            }
        } catch (error) {
            console.error('Error creating notification:', error);
        }
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notificationSystem = new NotificationSystem();
});

// Cleanup when page is unloaded
window.addEventListener('beforeunload', function() {
    if (window.notificationSystem) {
        window.notificationSystem.stopPolling();
    }
});
