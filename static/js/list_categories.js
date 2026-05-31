
    const dialog = document.getElementById('confirmDialog');
    let deleteId = null;
    
    function showToast(message, icon, isSuccess = true) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.borderColor = isSuccess ? '#ffd700' : '#ef4444';
        toast.innerHTML = `<span style="font-size: 18px;">${icon}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function confirmDelete(id, name) {
        deleteId = id;
        document.getElementById('deleteMessage').textContent = `آیا از حذف دسته‌بندی «${name}» اطمینان دارید؟`;
        dialog.style.display = 'flex';
    }
    
    function hideDialog() {
        dialog.style.display = 'none';
        deleteId = null;
    }
    
    document.getElementById('confirmBtn').addEventListener('click', function() {
        if (deleteId) {
            window.location.href = 'list_categories.php?delete=' + deleteId;
        }
        hideDialog();
    });
    
    dialog.addEventListener('click', function(e) {
        if (e.target === dialog) hideDialog();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dialog.style.display === 'flex') {
            hideDialog();
        }
    });
    
    const style = document.createElement('style');
    style.textContent = `@keyframes slideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(50px); } }`;
    document.head.appendChild(style);
