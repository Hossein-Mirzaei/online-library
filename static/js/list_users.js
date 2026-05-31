(function() {
    'use strict';
    
    let dialogCallback = null;
    const dialog = document.getElementById('confirmDialog');
    const dialogTitle = document.getElementById('dialogTitle');
    const dialogMessage = document.getElementById('dialogMessage');
    const dialogIcon = document.getElementById('dialogIcon');
    const dialogConfirm = document.getElementById('dialogConfirm');
    const dialogCancel = document.getElementById('dialogCancel');
    
    function showDialog(title, message, icon, confirmText, confirmClass, onConfirm) {
        dialogTitle.textContent = title;
        dialogMessage.textContent = message;
        dialogIcon.textContent = icon || '⚠️';
        dialogConfirm.textContent = confirmText || 'تأیید';
        dialogConfirm.className = 'alert-btn ' + (confirmClass || 'danger');
        dialogCallback = onConfirm;
        
        dialog.classList.remove('hidden');
        setTimeout(() => dialog.classList.add('show'), 10);
    }
    
    function hideDialog() {
        dialog.classList.remove('show');
        setTimeout(() => {
            dialog.classList.add('hidden');
            dialogCallback = null;
        }, 300);
    }
    
    function showToast(message, type) {
        const oldToast = document.querySelector('.toast-notification');
        if (oldToast) oldToast.remove();
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification ' + (type || 'success');
        toast.innerHTML = type === 'error' 
            ? '<span>❌</span> ' + message 
            : '<span>✅</span> ' + message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    window.toggleUserType = function(userId, currentType) {
        if (currentType === 'owner') {
            showToast('⛔ نوع حساب مالک سایت قابل تغییر نیست.', 'error');
            return;
        }
        
        const newTypeLabel = currentType === 'admin' ? 'کاربر عادی' : 'ادمین';
        
        showDialog(
            'تغییر نوع کاربر',
            `آیا از تغییر نوع این کاربر به «${newTypeLabel}» اطمینان دارید؟`,
            '🔄',
            'تغییر',
            'danger',
            function() {
                const formData = new FormData();
                formData.append('action', 'toggle_user_type');
                formData.append('user_id', userId);
                formData.append('current_type', currentType);
                
                fetch('list_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('badge-' + userId);
                        if (badge) {
                            badge.textContent = data.new_type === 'admin' ? '🛡️ ادمین' : '👤 کاربر عادی';
                            badge.className = 'user-type-badge ' + data.new_type;
                        }
                        
                        const row = document.getElementById('user-' + userId);
                        if (row) {
                            const toggleBtn = row.querySelector('.toggle-btn');
                            if (toggleBtn) {
                                const newType = data.new_type;
                                toggleBtn.setAttribute('onclick', `toggleUserType(${userId}, '${newType}')`);
                                toggleBtn.innerHTML = newType === 'admin' 
                                    ? '<span>🔄</span> تبدیل به کاربر' 
                                    : '<span>🔄</span> تبدیل به ادمین';
                            }
                        }
                        
                        showToast(data.message, 'success');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('خطا در ارتباط با سرور', 'error');
                });
                
                hideDialog();
            }
        );
    };
    
    window.confirmDelete = function(userId) {
        showDialog(
            'تأیید حذف کاربر',
            'آیا از حذف این کاربر اطمینان دارید؟ این عملیات قابل بازگشت نیست!',
            '🗑️',
            'حذف',
            'danger',
            function() {
                window.location.href = 'list_users.php?delete_id=' + userId;
            }
        );
    };
    
    dialogConfirm.addEventListener('click', function() {
        if (dialogCallback) {
            dialogCallback();
        }
    });
    
    dialogCancel.addEventListener('click', hideDialog);
    
    dialog.addEventListener('click', function(e) {
        if (e.target === dialog) hideDialog();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
            hideDialog();
        }
    });
    
})();