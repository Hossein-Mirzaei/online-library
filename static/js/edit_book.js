document.addEventListener('DOMContentLoaded', function() {
    
    function previewImage(event) {
        const file = event.target.files[0];
        const newImage = document.getElementById('newImage');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                newImage.src = e.target.result;
                newImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            newImage.style.display = 'none';
        }
    }
    
    function showToast(message, icon, isSuccess = true) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        if (!isSuccess) toast.classList.add('error');
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function addDynamicStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .toast-notification {
                position: fixed;
                bottom: 20px;
                left: 20px;
                background: rgba(15, 23, 42, 0.9);
                backdrop-filter: blur(10px);
                color: white;
                padding: 12px 20px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 14px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                border: 1px solid rgba(255, 215, 0, 0.3);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            }
            
            .toast-notification.error {
                border-color: rgba(239, 68, 68, 0.5);
                background: rgba(127, 29, 29, 0.9);
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(50px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(50px);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    function validateForm(event) {
        const yearInput = document.getElementById('publication_year');
        const priceInput = document.getElementById('price');
        const currentYear = window.APP_CONFIG ? window.APP_CONFIG.currentYear : new Date().getFullYear();
        
        if (!yearInput || !priceInput) return true;
        
        if (yearInput.value < 1000 || yearInput.value > currentYear) {
            event.preventDefault();
            showToast('سال انتشار باید بین 1000 و ' + currentYear + ' باشد.', '⚠️', false);
            yearInput.focus();
            return false;
        }
        
        if (priceInput.value < 0) {
            event.preventDefault();
            showToast('قیمت نمی‌تواند منفی باشد.', '⚠️', false);
            priceInput.focus();
            return false;
        }
        
        return true;
    }
    
    function initEventListeners() {
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', previewImage);
        }
        
        const editForm = document.getElementById('editForm');
        if (editForm) {
            editForm.addEventListener('submit', validateForm);
        }
    }
    
    function init() {
        addDynamicStyles();
        initEventListeners();
        
        console.log('Edit Book JS loaded successfully');
    }
    
    init();
    
    window.previewImage = previewImage;
    window.showToast = showToast;
});