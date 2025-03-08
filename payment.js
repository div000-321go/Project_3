import { api, notifications } from './main.js';

class PaymentHandler {
    constructor() {
        this.form = document.getElementById('paymentForm');
        this.promotionData = JSON.parse(localStorage.getItem('promotionData'));
        
        this.init();
    }

    init() {
        // Load promotion data
        this.loadPromotionData();

        // Set up payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => this.handleMethodSelection(method));
        });

        // Set up form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Set up card input formatting
        this.setupCardInputs();
    }

    loadPromotionData() {
        if (!this.promotionData) {
            window.location.href = 'promote.html';
            return;
        }

        document.getElementById('packageType').textContent = this.promotionData.packageType;
        document.getElementById('packageAmount').textContent = 
            formatCurrency(this.promotionData.packagePrice);
        document.getElementById('packageDuration').textContent = 
            this.promotionData.packageType === 'premium' ? '60 Days' : 
            this.promotionData.packageType === 'enterprise' ? '90 Days' : '30 Days';
    }

    handleMethodSelection(method) {
        document.querySelectorAll('.payment-method').forEach(m => {
            m.classList.remove('selected');
        });
        method.classList.add('selected');

        // Show/hide relevant form fields
        const cardDetails = document.querySelectorAll('.card-details');
        if (method.dataset.method === 'card') {
            cardDetails.forEach(detail => detail.style.display = 'block');
        } else {
            cardDetails.forEach(detail => detail.style.display = 'none');
        }
    }

    setupCardInputs() {
        // Card number formatting
        const cardInput = document.querySelector('input[placeholder="1234 5678 9012 3456"]');
        cardInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = value;
        });

        // Expiry date formatting
        const expiryInput = document.querySelector('input[placeholder="MM/YY"]');
        expiryInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });
    }

    async handleSubmit(e) {
        e.preventDefault();

        const selectedMethod = document.querySelector('.payment-method.selected');
        if (!selectedMethod) {
            notifications.show('Please select a payment method', 'error');
            return;
        }

        const paymentData = {
            product_id: this.promotionData.id,
            amount: this.promotionData.packagePrice,
            payment_method: selectedMethod.dataset.method,
            email: this.promotionData.email
        };

        try {
            const response = await api.post('payment.php', paymentData);
            if (response.status === 'success') {
                // Show success message
                document.querySelector('.payment-details').style.display = 'none';
                document.querySelector('.success-message').style.display = 'block';
                document.getElementById('transactionId').textContent = response.transaction_id;

                // Clear stored promotion data
                localStorage.removeItem('promotionData');

                // Redirect after delay
                setTimeout(() => {
                    window.location.href = 'products.html';
                }, 3000);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            notifications.show(error.message, 'error');
        }
    }
}

export default PaymentHandler;