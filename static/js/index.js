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

    window.addToCart = function(bookId) {
        const btn = event.target.closest('button');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span>⏳</span><span>...</span>';
        btn.disabled = true;

        fetch('../api/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            if (data.success) {
                showToast(data.message || 'کتاب با موفقیت به سبد خرید اضافه شد.', '✅', true);
            } else {
                showToast(data.message || 'مشکلی پیش آمده است.', '❌', false);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            showToast('خطا در ارتباط با سرور.', '❌', false);
        });
    };

    function initDragScroll() {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;

        let isDown = false, startX, scrollLeft;

        container.addEventListener('mousedown', (e) => {
            isDown = true;
            container.style.cursor = 'grabbing';
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });

        container.addEventListener('mouseleave', () => {
            isDown = false;
            container.style.cursor = 'grab';
        });

        container.addEventListener('mouseup', () => {
            isDown = false;
            container.style.cursor = 'grab';
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        });
    }

    document.addEventListener('DOMContentLoaded', initDragScroll);
    
    const style = document.createElement('style');
    style.textContent = `@keyframes slideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(50px); } }`;
    document.head.appendChild(style);
})();