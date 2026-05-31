
    (function() {
        'use strict';
        
        const modal = document.getElementById('userDetailsModal');
        const modalBody = document.getElementById('userDetailsBody');
        const confirmDialog = document.getElementById('confirmDialog');
        let pendingDeleteId = null;
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + type;
            toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function showConfirmDialog(message, onConfirm) {
            document.getElementById('confirmMessage').textContent = message;
            confirmDialog.classList.remove('hidden');
            setTimeout(() => confirmDialog.classList.add('show'), 10);
            
            document.getElementById('confirmBtn').onclick = function() {
                hideConfirmDialog();
                if (onConfirm) onConfirm();
            };
        }
        
        window.hideConfirmDialog = function() {
            confirmDialog.classList.remove('show');
            setTimeout(() => confirmDialog.classList.add('hidden'), 300);
        };
        
        window.deleteMessage = function(messageId) {
            showConfirmDialog('آیا از حذف این پیام اطمینان دارید؟', function() {
                const formData = new FormData();
                formData.append('action', 'delete_message');
                formData.append('message_id', messageId);
                
                fetch('list_messages.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById('message-' + messageId);
                        if (row) {
                            row.style.transition = 'all 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                if (document.querySelectorAll('.messages-table tbody tr').length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        }
                        showToast(data.message, 'success');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('خطا در ارتباط با سرور', 'error'));
            });
        };
        
        window.showUserDetails = function(email) {
            modal.classList.add('active');
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="loading-spinner"></div>
                    <p>در حال بارگذاری اطلاعات...</p>
                </div>
            `;
            
            fetch('list_messages.php?action=get_user_info&email=' + encodeURIComponent(email))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        const typeLabel = user.user_type === 'admin' ? 'مدیر' : 'کاربر عادی';
                        const typeClass = user.user_type === 'admin' ? 'admin' : 'user';
                        
                        modalBody.innerHTML = `
                            <div class="user-detail-item">
                                <span class="user-detail-label">نام و نام خانوادگی:</span>
                                <span class="user-detail-value">${escapeHtml(user.first_name + ' ' + user.last_name)}</span>
                            </div>
                            <div class="user-detail-item">
                                <span class="user-detail-label">نام کاربری:</span>
                                <span class="user-detail-value">${escapeHtml(user.username)}</span>
                            </div>
                            <div class="user-detail-item">
                                <span class="user-detail-label">ایمیل:</span>
                                <span class="user-detail-value">${escapeHtml(user.email)}</span>
                            </div>
                            <div class="user-detail-item">
                                <span class="user-detail-label">نوع کاربر:</span>
                                <span class="user-detail-value">${typeLabel}</span>
                            </div>
                            <div class="user-detail-item">
                                <span class="user-detail-label">آدرس:</span>
                                <span class="user-detail-value">${escapeHtml(user.address || 'ثبت نشده')}</span>
                            </div>
                            <div class="user-detail-item">
                                <span class="user-detail-label">تاریخ عضویت:</span>
                                <span class="user-detail-value">${formatDate(user.created_at)}</span>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon">❌</div>
                                <h3>کاربر یافت نشد</h3>
                                <p>این کاربر در سیستم ثبت‌نام نکرده است.</p>
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
        
        window.closeUserDetails = function() {
            modal.classList.remove('active');
        };
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('fa-IR');
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeUserDetails();
            }
        });
        
        confirmDialog.addEventListener('click', function(e) {
            if (e.target === confirmDialog) {
                hideConfirmDialog();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (modal.classList.contains('active')) closeUserDetails();
                if (!confirmDialog.classList.contains('hidden')) hideConfirmDialog();
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
