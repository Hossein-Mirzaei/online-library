
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
    
    function addToCart(bookId) {
        const btn = event.target.closest('button');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span>⏳</span><span>...</span>';
        btn.disabled = true;
        
        fetch('../api/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            
            if (data.success) {
                showToast(data.message || 'کتاب با موفقیت به سبد خرید اضافه شد.', '✅', true);
            } else {
                showToast(data.message || 'خطا در افزودن به سبد خرید.', '❌', false);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            showToast('خطا در ارتباط با سرور.', '❌', false);
        });
    }
