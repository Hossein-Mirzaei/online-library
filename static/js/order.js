
(function() {
    'use strict';
    
    function showToast(message, icon, isSuccess = true) {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.borderColor = isSuccess ? '#ffd700' : '#ef4444';
        toast.innerHTML = `<span style="font-size: 16px;">${icon}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
    
    const confirmDialog = {
        overlay: document.getElementById('confirmDialog'),
        messageEl: document.getElementById('confirmMessage'),
        cancelBtn: document.getElementById('confirmCancel'),
        okBtn: document.getElementById('confirmOk'),
        
        show: function(message, onConfirm) {
            this.messageEl.textContent = message;
            this.overlay.classList.remove('hidden');
            this.overlay.classList.add('show');
            
            const self = this;
            
            this.cancelBtn.onclick = function() {
                self.hide();
            };
            
            this.okBtn.onclick = function() {
                self.hide();
                if (onConfirm) onConfirm();
            };
            
            this.overlay.onclick = function(e) {
                if (e.target === self.overlay) {
                    self.hide();
                }
            };
        },
        
        hide: function() {
            this.overlay.classList.remove('show');
            this.overlay.classList.add('hidden');
        }
    };
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    function updateCartTotal() {
        const totals = document.querySelectorAll('.item-total');
        let sum = 0;
        totals.forEach(el => {
            const text = el.textContent.replace(/[^\d]/g, '');
            sum += parseInt(text) || 0;
        });
        document.getElementById('total-price').textContent = formatNumber(sum) + ' تومان';
    }
    
    function ajaxRequest(data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback(new Error('پاسخ نامعتبر از سرور'));
                    }
                } else {
                    callback(new Error('خطا در ارتباط با سرور'));
                }
            }
        };
        
        const params = Object.keys(data).map(key => 
            encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
        ).join('&');
        
        xhr.send(params);
    }
    
    function initPage() {
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function() {
                showToast('درگاه پرداخت در حال راه‌اندازی است. به زودی فعال خواهد شد.', '🏦', true);
            });
        }
        
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const bookName = this.dataset.bookName || 'این کتاب';
                const row = document.getElementById('order-' + orderId);
                
                confirmDialog.show(
                    `آیا از حذف «${bookName}» از سبد خرید اطمینان دارید؟`,
                    function() {
                        btn.disabled = true;
                        const originalContent = btn.innerHTML;
                        btn.innerHTML = '<span>⏳</span>';
                        
                        ajaxRequest({ action: 'remove', order_id: orderId }, function(err, response) {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                            
                            if (err) {
                                showToast('ارتباط با سرور برقرار نشد.', '❌', false);
                                return;
                            }
                            
                            if (response.success) {
                                if (row) {
                                    row.style.transition = 'all 0.3s';
                                    row.style.opacity = '0';
                                    row.style.transform = 'translateX(-20px)';
                                    setTimeout(() => {
                                        row.remove();
                                        updateCartTotal();
                                        
                                        if (document.querySelectorAll('.order-item').length === 0) {
                                            location.reload();
                                        }
                                    }, 300);
                                }
                                showToast(response.message, '✅', true);
                            } else {
                                showToast(response.message, '❌', false);
                            }
                        });
                    }
                );
            });
        });
        
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const price = parseFloat(this.dataset.price);
                let quantity = parseInt(this.value);
                
                if (quantity < 1) {
                    quantity = 1;
                    this.value = 1;
                }
                
                const row = document.getElementById('order-' + orderId);
                const totalEl = row.querySelector('.item-total');
                const originalValue = this.value;
                
                this.disabled = true;
                
                ajaxRequest({ 
                    action: 'update', 
                    order_id: orderId, 
                    quantity: quantity 
                }, function(err, response) {
                    input.disabled = false;
                    
                    if (err) {
                        showToast('ارتباط با سرور برقرار نشد.', '❌', false);
                        input.value = originalValue;
                        return;
                    }
                    
                    if (response.success) {
                        totalEl.textContent = response.item_total + ' تومان';
                        document.getElementById('total-price').textContent = response.cart_total + ' تومان';
                        showToast(response.message, '✅', true);
                    } else {
                        showToast(response.message, '❌', false);
                        input.value = originalValue;
                    }
                });
            });
            
            input.addEventListener('input', function() {
                if (this.value < 1) this.value = 1;
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPage);
    } else {
        initPage();
    }
})();
