
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    const username = document.getElementById('username_or_email').value.trim();
                    const password = document.getElementById('password').value;
                    
                    if (!username || !password) {
                        e.preventDefault();
                        alert('❌ لطفاً نام کاربری/ایمیل و رمز عبور را وارد کنید.');
                        return;
                    }
                    
                    const btnText = submitBtn.querySelector('span:first-child');
                    const originalText = btnText.textContent;
                    
                    submitBtn.disabled = true;
                    btnText.innerHTML = '<span class="loading-spinner"></span> در حال ورود...';
                    
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.disabled = false;
                            btnText.textContent = originalText;
                        }
                    }, 5000);
                });
            }
            
            const passwordField = document.getElementById('password');
            const passwordWrapper = passwordField.closest('.input-wrapper');
            if (passwordWrapper) {
                const icon = passwordWrapper.querySelector('.input-icon');
                if (icon) {
                    icon.style.cursor = 'pointer';
                    icon.style.pointerEvents = 'auto';
                    icon.addEventListener('click', function() {
                        if (passwordField.type === 'password') {
                            passwordField.type = 'text';
                            icon.textContent = '👁️';
                        } else {
                            passwordField.type = 'password';
                            icon.textContent = '🔒';
                        }
                    });
                }
            }
        });
    