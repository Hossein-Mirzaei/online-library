(function() {
    'use strict';
    
    function showToast(message, icon, isSuccess = true) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.borderColor = isSuccess ? '#ffd700' : '#ef4444';
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
    
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(feedbackForm);
            fetch('../api/send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('نظر شما با موفقیت ارسال شد. متشکریم!', '💬', true);
                    feedbackForm.reset();
                } else {
                    showToast(data.message || 'خطا در ارسال پیام.', '❌', false);
                }
            })
            .catch(() => {
                showToast('خطا در ارتباط با سرور.', '❌', false);
            });
        });
    }
})();