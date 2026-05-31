
    (function() {
        'use strict';
        
        let deleteBookId = null;
        
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
        
        const detailsModal = document.getElementById('detailsModal');
        const modalCaption = document.getElementById('modalCaption');
        const modalDescription = document.getElementById('modalDescription');
        
        function openModal(caption, description) {
            modalCaption.textContent = caption || 'ندارد';
            modalDescription.textContent = description || 'ندارد';
            detailsModal.classList.add('active');
        }
        
        function closeModal() {
            detailsModal.classList.remove('active');
        }
        
        const deleteModal = document.getElementById('deleteModal');
        const deleteMessage = document.getElementById('deleteMessage');
        
        function openDeleteModal(bookId, bookName) {
            deleteBookId = bookId;
            deleteMessage.textContent = `آیا از حذف کتاب «${bookName}» اطمینان دارید؟`;
            deleteModal.classList.add('active');
        }
        
        function closeDeleteModal() {
            deleteModal.classList.remove('active');
            deleteBookId = null;
        }
        
        function deleteBook() {
            if (!deleteBookId) return;
            
            fetch('delete_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + deleteBookId
            })
            .then(response => response.json())
            .then(data => {
                closeDeleteModal();
                if (data.success) {
                    showToast('کتاب با موفقیت حذف شد.', '✅');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'خطا در حذف کتاب.', '❌', false);
                }
            })
            .catch(() => {
                closeDeleteModal();
                showToast('خطا در ارتباط با سرور.', '❌', false);
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.details-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const caption = this.dataset.caption;
                    const description = this.dataset.description;
                    openModal(caption, description);
                });
            });
            
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const bookId = this.dataset.id;
                    const bookName = this.dataset.name;
                    openDeleteModal(bookId, bookName);
                });
            });
            
            document.querySelectorAll('.modal-close').forEach(btn => {
                btn.addEventListener('click', function() {
                    detailsModal.classList.remove('active');
                    deleteModal.classList.remove('active');
                });
            });
            
            [detailsModal, deleteModal].forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.remove('active');
                    }
                });
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    detailsModal.classList.remove('active');
                    deleteModal.classList.remove('active');
                }
            });
            
            document.getElementById('confirmDeleteBtn').addEventListener('click', deleteBook);
        });
        
        window.closeDeleteModal = closeDeleteModal;
    })();
