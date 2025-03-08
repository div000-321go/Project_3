// Global variables
const API_URL = 'http://localhost/promohub/api';

// Utility functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Animation utilities
const animateElement = (element, animation) => {
    element.style.animation = 'none';
    element.offsetHeight; // Trigger reflow
    element.style.animation = animation;
};

const fadeIn = (element, duration = 500) => {
    element.style.opacity = 0;
    element.style.display = 'block';
    element.style.transition = `opacity ${duration}ms ease`;
    setTimeout(() => element.style.opacity = 1, 10);
};

const fadeOut = (element, duration = 500) => {
    element.style.opacity = 1;
    element.style.transition = `opacity ${duration}ms ease`;
    element.style.opacity = 0;
    setTimeout(() => element.style.display = 'none', duration);
};

// API calls
const api = {
    async get(endpoint) {
        try {
            const response = await fetch(`${API_URL}/${endpoint}`);
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async post(endpoint, data) {
        try {
            const response = await fetch(`${API_URL}/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
};

// Notification system
const notifications = {
    show(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);
        fadeIn(notification);

        setTimeout(() => {
            fadeOut(notification);
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
};

// Form validation
const validateForm = (formData, rules) => {
    const errors = {};

    for (const [field, value] of formData.entries()) {
        if (rules[field]) {
            if (rules[field].required && !value) {
                errors[field] = `${field} is required`;
            }
            if (rules[field].minLength && value.length < rules[field].minLength) {
                errors[field] = `${field} must be at least ${rules[field].minLength} characters`;
            }
            if (rules[field].pattern && !rules[field].pattern.test(value)) {
                errors[field] = `${field} format is invalid`;
            }
        }
    }

    return errors;
};

// Export functions
export {
    api,
    notifications,
    validateForm,
    formatCurrency,
    formatDate,
    animateElement,
    fadeIn,
    fadeOut
};