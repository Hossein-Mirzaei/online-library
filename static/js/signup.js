
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function() {
                    const btnText = submitBtn.querySelector('span:first-child');
                    const originalText = btnText.textContent;
                    
                    submitBtn.disabled = true;
                    btnText.innerHTML = '<span class="loading-spinner"></span> لطفاً صبر کنید...';
                    
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.disabled = false;
                            btnText.textContent = originalText;
                        }
                    }, 5000);
                });
            }
            
            const passwordFields = document.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                const wrapper = field.closest('.input-wrapper');
                if (wrapper) {
                    const icon = wrapper.querySelector('.input-icon');
                    if (icon) {
                        icon.style.cursor = 'pointer';
                        icon.style.pointerEvents = 'auto';
                        icon.addEventListener('click', function() {
                            if (field.type === 'password') {
                                field.type = 'text';
                                icon.textContent = '👁️';
                            } else {
                                field.type = 'password';
                                icon.textContent = '🔒';
                            }
                        });
                    }
                }
            });
        });
    