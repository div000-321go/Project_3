import { api, notifications, validateForm } from './main.js';

class PromotionForm {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.imageUpload = document.getElementById('imageUpload');
        this.imagePreview = document.createElement('div');
        this.imagePreview.className = 'image-preview';
        this.selectedFiles = [];

        this.init();
    }

    init() {
        // Set up image upload
        this.setupImageUpload();

        // Set up form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Set up package selection
        document.querySelectorAll('.price-option').forEach(option => {
            option.addEventListener('click', () => this.handlePackageSelection(option));
        });
    }

    setupImageUpload() {
        // Add image preview container
        this.imageUpload.parentNode.insertBefore(this.imagePreview, this.imageUpload.nextSibling);

        // Handle drag and drop
        this.imageUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.imageUpload.classList.add('dragover');
        });

        this.imageUpload.addEventListener('dragleave', () => {
            this.imageUpload.classList.remove('dragover');
        });

        this.imageUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            this.imageUpload.classList.remove('dragover');
            const files = e.dataTransfer.files;
            this.handleFiles(files);
        });

        // Handle file input change
        document.getElementById('productImages').addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
    }

    handleFiles(files) {
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                this.selectedFiles.push(file);
                this.previewImage(file);
            }
        });
    }

    previewImage(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.createElement('div');
            preview.className = 'preview-item';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-btn">
                    <i class="fas fa-times"></i>
                </button>
            `;

            preview.querySelector('.remove-btn').addEventListener('click', () => {
                const index = this.selectedFiles.indexOf(file);
                if (index > -1) {
                    this.selectedFiles.splice(index, 1);
                }
                preview.remove();
            });

            this.imagePreview.appendChild(preview);
        };
        reader.readAsDataURL(file);
    }

    handlePackageSelection(option) {
        document.querySelectorAll('.price-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        option.classList.add('selected');
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.form);
        
        // Validate form
        const rules = {
            companyName: { required: true, minLength: 2 },
            productName: { required: true, minLength: 2 },
            description: { required: true, minLength: 20 },
            category: { required: true }
        };

        const errors = validateForm(formData, rules);

        if (Object.keys(errors).length > 0) {
            Object.entries(errors).forEach(([field, message]) => {
                notifications.show(message, 'error');
            });
            return;
        }

        // Add selected package
        const selectedPackage = document.querySelector('.price-option.selected');
        if (!selectedPackage) {
            notifications.show('Please select a promotion package', 'error');
            return;
        }

        formData.append('packageType', selectedPackage.dataset.type);
        formData.append('packagePrice', selectedPackage.dataset.price);

        // Add images
        this.selectedFiles.forEach(file => {
            formData.append('images[]', file);
        });

        try {
            const response = await api.post('promote.php', formData);
            if (response.status === 'success') {
                notifications.show('Product submitted successfully!');
                // Store promotion data for payment
                localStorage.setItem('promotionData', JSON.stringify(response.data));
                // Redirect to payment page
                window.location.href = 'payment.html';
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            notifications.show(error.message, 'error');
        }
    }
}

export default PromotionForm;