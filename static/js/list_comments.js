
    let selectedIds = new Set(JSON.parse(localStorage.getItem('selectedComments') || '[]'));
    let dialogCallback = null;
    const dialog = document.getElementById('confirmDialog');
    const dialogTitle = document.getElementById('dialogTitle');
    const dialogMessage = document.getElementById('dialogMessage');
    const dialogIcon = document.getElementById('dialogIcon');
    const dialogConfirm = document.getElementById('dialogConfirm');
    const dialogCancel = document.getElementById('dialogCancel');
    
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.comment-checkbox');
        const newSelected = new Set();
        
        checkboxes.forEach(cb => {
            if (cb.checked) {
                newSelected.add(cb.value);
            }
        });
        
        selectedIds = newSelected;
        localStorage.setItem('selectedComments', JSON.stringify([...selectedIds]));
        updateUI();
    }
    
    function updateUI() {
        const count = selectedIds.size;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkDeleteBtn').disabled = count === 0;
        
        const allCheckbox = document.getElementById('selectAllCheckbox');
        const visibleCheckboxes = document.querySelectorAll('.comment-checkbox');
        if (visibleCheckboxes.length > 0) {
            allCheckbox.checked = visibleCheckboxes.length === count && count > 0;
            allCheckbox.indeterminate = count > 0 && count < visibleCheckboxes.length;
        }
        
        document.querySelectorAll('.comment-checkbox').forEach(cb => {
            cb.checked = selectedIds.has(cb.value);
        });
    }
    
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.comment-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateSelection();
    }
    
    function clearAllSelections() {
        selectedIds.clear();
        localStorage.setItem('selectedComments', '[]');
        document.querySelectorAll('.comment-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllCheckbox').checked = false;
        document.getElementById('selectAllCheckbox').indeterminate = false;
        updateUI();
    }
    
    function showDialog(title, message, icon, confirmText, onConfirm) {
        dialogTitle.textContent = title;
        dialogMessage.textContent = message;
        dialogIcon.textContent = icon || '⚠️';
        dialogConfirm.textContent = confirmText || 'تأیید';
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
        const toast = document.createElement('div');
        toast.className = 'toast-notification ' + type;
        toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function ajaxRequest(data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        callback(null, JSON.parse(xhr.responseText));
                    } catch (e) {
                        callback(new Error('پاسخ نامعتبر'));
                    }
                } else {
                    callback(new Error('خطا در ارتباط'));
                }
            }
        };
        
        const params = Object.keys(data).map(key => 
            encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
        ).join('&');
        
        xhr.send(params);
    }
    
    function deleteSingle(commentId) {
        showDialog(
            'تأیید حذف',
            'آیا از حذف این نظر اطمینان دارید؟',
            '🗑️',
            'حذف',
            function() {
                ajaxRequest({ action: 'delete', comment_id: commentId }, function(err, response) {
                    if (err) {
                        showToast('خطا در ارتباط با سرور', 'error');
                        return;
                    }
                    
                    if (response.success) {
                        const row = document.getElementById('comment-' + commentId);
                        if (row) {
                            row.remove();
                            selectedIds.delete(commentId.toString());
                            localStorage.setItem('selectedComments', JSON.stringify([...selectedIds]));
                            updateUI();
                        }
                        showToast(response.message, 'success');
                        setTimeout(() => { if (document.querySelectorAll('.comment-checkbox').length === 0) location.reload(); }, 500);
                    } else {
                        showToast(response.message, 'error');
                    }
                });
                hideDialog();
            }
        );
    }
    
    function bulkDelete() {
        if (selectedIds.size === 0) {
            showToast('هیچ نظری انتخاب نشده است.', 'error');
            return;
        }
        
        showDialog(
            'تأیید حذف گروهی',
            `آیا از حذف ${selectedIds.size} نظر انتخاب شده اطمینان دارید؟ این عمل قابل بازگشت نیست.`,
            '🗑️',
            'حذف همه',
            function() {
                ajaxRequest({ 
                    action: 'bulk_delete', 
                    ids: JSON.stringify([...selectedIds])
                }, function(err, response) {
                    if (err) {
                        showToast('خطا در ارتباط با سرور', 'error');
                        return;
                    }
                    
                    if (response.success) {
                        selectedIds.forEach(id => {
                            const row = document.getElementById('comment-' + id);
                            if (row) row.remove();
                        });
                        clearAllSelections();
                        showToast(response.message, 'success');
                        setTimeout(() => { if (document.querySelectorAll('.comment-checkbox').length === 0) location.reload(); }, 500);
                    } else {
                        showToast(response.message, 'error');
                    }
                });
                hideDialog();
            }
        );
    }
    
    function toggleResponseForm(commentId) {
        const form = document.getElementById('response-form-' + commentId);
        const allForms = document.querySelectorAll('.response-form');
        allForms.forEach(f => { if (f.id !== 'response-form-' + commentId) f.style.display = 'none'; });
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
    
    function submitResponse(commentId) {
        const textarea = document.getElementById('response-text-' + commentId);
        const response = textarea.value.trim();
        
        if (!response) {
            showToast('پاسخ نمی‌تواند خالی باشد', 'error');
            return;
        }
        
        ajaxRequest({ action: 'respond', comment_id: commentId, response: response }, function(err, result) {
            if (err) {
                showToast('خطا در ارتباط با سرور', 'error');
                return;
            }
            
            if (result.success) {
                const responseDiv = document.getElementById('response-' + commentId);
                if (responseDiv) {
                    responseDiv.innerHTML = result.response + 
                        '<div class="response-date">' + 
                        new Date(result.response_date).toLocaleString('fa-IR') + 
                        '</div>';
                    responseDiv.className = 'admin-response has-response';
                }
                document.getElementById('response-form-' + commentId).style.display = 'none';
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'error');
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        updateUI();
        
        document.querySelectorAll('.action-btn.respond').forEach(btn => {
            if (!btn.hasAttribute('data-listener')) {
                btn.setAttribute('data-listener', 'true');
            }
        });
    });
    
    dialogConfirm.addEventListener('click', function() {
        if (dialogCallback) dialogCallback();
        else hideDialog();
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
    
    window.toggleSelectAll = toggleSelectAll;
    window.updateSelection = updateSelection;
    window.clearAllSelections = clearAllSelections;
    window.bulkDelete = bulkDelete;
    window.deleteSingle = deleteSingle;
    window.toggleResponseForm = toggleResponseForm;
    window.submitResponse = submitResponse;
    
    const style = document.createElement('style');
    style.textContent = `@keyframes slideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(50px); } }`;
    document.head.appendChild(style);
