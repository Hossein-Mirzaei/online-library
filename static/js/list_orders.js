
    (function() {
        'use strict';
        
        const modal = document.getElementById('orderModal');
        const modalBody = document.getElementById('orderDetailsBody');
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.style.borderColor = type === 'success' ? '#28a745' : '#dc3545';
            toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        window.showOrderDetails = function(userId) {
            modal.classList.add('active');
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>در حال بارگذاری اطلاعات...</p>
                </div>
            `;
            
            fetch('list_orders.php?action=get_orders&user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.orders.length === 0) {
                            modalBody.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <h3>سفارشی یافت نشد</h3>
                                    <p>این کاربر هیچ سفارشی ثبت نکرده است.</p>
                                </div>
                            `;
                            return;
                        }
                        
                        let html = `
                            <table class="order-items-table">
                                <thead>
                                    <tr>
                                        <th>کتاب</th>
                                        <th>نویسنده</th>
                                        <th>قیمت واحد</th>
                                        <th>تعداد</th>
                                        <th>قیمت کل</th>
                                        <th>تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        data.orders.forEach(order => {
                            html += `
                                <tr>
                                    <td style="text-align: right;">${escapeHtml(order.book_name)}</td>
                                    <td style="color: var(--text-muted);">${escapeHtml(order.author || '-')}</td>
                                    <td>${numberFormat(order.unit_price)} تومان</td>
                                    <td>${order.quantity}</td>
                                    <td style="color: var(--success-color); font-weight: 600;">${numberFormat(order.total_price)} تومان</td>
                                    <td>${formatDate(order.order_date)}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                </tbody>
                            </table>
                            <div class="modal-total">
                                مجموع کل: ${numberFormat(data.total_amount)} تومان
                                (${data.count} سفارش)
                            </div>
                        `;
                        
                        modalBody.innerHTML = html;
                    } else {
                        modalBody.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon">❌</div>
                                <h3>خطا در بارگذاری</h3>
                                <p>مشکلی در دریافت اطلاعات پیش آمد.</p>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    modalBody.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">❌</div>
                            <h3>خطا در ارتباط</h3>
                            <p>لطفاً دوباره تلاش کنید.</p>
                        </div>
                    `;
                });
        };
        
        window.closeOrderModal = function() {
            modal.classList.remove('active');
        };
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function numberFormat(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        function formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('fa-IR');
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeOrderModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeOrderModal();
            }
        });
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(50px); }
            }
        `;
        document.head.appendChild(style);
    })();
