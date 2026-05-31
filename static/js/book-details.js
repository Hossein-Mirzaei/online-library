(function() {
    'use strict';
    
    const BOOK_ID = window.BOOK_DETAILS?.bookId || 0;
    const TOTAL_COMMENTS = window.BOOK_DETAILS?.totalComments || 0;
    const COMMENT_MESSAGE = window.BOOK_DETAILS?.commentMessage;
    const COMMENT_STATUS = window.BOOK_DETAILS?.commentStatus;
    
    let analysisPerformed = false;
    
    window.showCustomAlert = function(title, message, icon) {
        const overlay = document.getElementById('customAlert');
        const titleEl = document.getElementById('alertTitle');
        const msgEl = document.getElementById('alertMessage');
        const iconEl = document.getElementById('alertIcon');
        
        if (!overlay) return;
        
        titleEl.textContent = title;
        msgEl.textContent = message;
        iconEl.textContent = icon === 'success' ? '✅' : (icon === 'error' ? '❌' : (icon || '✅'));
        
        overlay.classList.remove('hidden');
        overlay.classList.add('custom-alert-show');
        
        document.getElementById('alertButton').onclick = function() {
            overlay.classList.add('hidden');
            overlay.classList.remove('custom-alert-show');
        };
        
        overlay.onclick = function(e) {
            if (e.target === overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('custom-alert-show');
            }
        };
    };
    
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
                showToast(data.message || 'خطا در افزودن به سبد خرید.', '❌', false);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            showToast('خطا در ارتباط با سرور.', '❌', false);
        });
    };
    
    window.requestAIAnalysis = async function() {
        const contentDiv = document.getElementById('aiAnalysisContent');
        const analyzeBtn = document.getElementById('analyzeBtn');
        const commentCountSpan = document.getElementById('commentCount');
        
        if (analysisPerformed || !analyzeBtn) return;
        
        if (TOTAL_COMMENTS === 0) {
            contentDiv.innerHTML = `
                <div class="ai-placeholder">
                    <span class="placeholder-icon">📭</span>
                    <span>هنوز نظری برای این کتاب ثبت نشده است.</span>
                </div>
            `;
            analyzeBtn.style.display = 'none';
            return;
        }
        
        contentDiv.innerHTML = `
            <div class="ai-loading">
                <div class="ai-loading-spinner"></div>
                <span>در حال تحلیل نظرات کاربران...</span>
            </div>
        `;
        
        analyzeBtn.disabled = true;
        analyzeBtn.innerHTML = '<span>⏳</span><span>در حال تحلیل...</span>';
        
        try {
            const response = await fetch(`../api/ai_analysis.php?book_id=${BOOK_ID}`);
            const data = await response.json();
            
            if (data.success) {
                if (commentCountSpan && data.stats) {
                    commentCountSpan.textContent = data.stats.total_comments;
                }
                
                let statsHTML = '';
                if (data.stats) {
                    const stats = data.stats;
                    statsHTML = `
                        <div class="ai-stats-inline">
                            <div class="ai-stat-item">
                                <span class="stat-icon">⭐</span>
                                <span>میانگین:</span>
                                <span class="stat-value">${stats.avg_rating}</span>
                                <span>از ۵</span>
                            </div>
                            <div class="ai-stat-item">
                                <span class="stat-icon">👍</span>
                                <span>رضایت:</span>
                                <span class="stat-value">${stats.satisfaction_rate}%</span>
                            </div>
                            <div class="ai-stat-item">
                                <span class="stat-icon">💬</span>
                                <span>نظرات:</span>
                                <span class="stat-value">${stats.total_comments}</span>
                            </div>
                        </div>
                        <div class="ai-stars-distribution">
                            ${[5,4,3,2,1].map(r => `
                                <div class="ai-star-bar">
                                    <span class="stars-visual">${'★'.repeat(r)}${'☆'.repeat(5-r)}</span>
                                    <span>(${stats.distribution[r]})</span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                
                let analysisHTML = data.analysis
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/^- (.*?)$/gm, '<li>$1</li>')
                    .replace(/((?:<li>.*?<\/li>\s*)+)/g, '<ul>$1</ul>')
                    .replace(/\n/g, '<br>');
                
                contentDiv.className = 'ai-content fade-in';
                contentDiv.innerHTML = statsHTML + analysisHTML;
                
                analysisPerformed = true;
                analyzeBtn.style.display = 'none';
                
            } else {
                contentDiv.className = 'ai-content';
                contentDiv.innerHTML = `
                    <div class="ai-placeholder">
                        <span class="placeholder-icon">🔌</span>
                        <span>${data.message || 'سرویس هوش مصنوعی در حال حاضر در دسترس نیست.'}</span>
                        <span style="font-size: 0.7rem; color: rgba(255,255,255,0.3);">لطفاً بعداً دوباره تلاش کنید.</span>
                    </div>
                `;
                analyzeBtn.disabled = false;
                analyzeBtn.innerHTML = '<span>🔍</span><span>تلاش مجدد</span>';
                analysisPerformed = false; // اجازه تلاش مجدد
            }
        } catch (error) {
            contentDiv.className = 'ai-content';
            contentDiv.innerHTML = `
                <div class="ai-placeholder">
                    <span class="placeholder-icon">🌐</span>
                    <span>خطا در ارتباط با سرور.</span>
                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.3);">لطفاً اتصال اینترنت خود را بررسی کنید.</span>
                </div>
            `;
            analyzeBtn.disabled = false;
            analyzeBtn.innerHTML = '<span>🔍</span><span>تلاش مجدد</span>';
            analysisPerformed = false; // اجازه تلاش مجدد
        }
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        const analyzeBtn = document.getElementById('analyzeBtn');
        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', window.requestAIAnalysis);
        }
        
        if (COMMENT_MESSAGE) {
            window.showCustomAlert(
                COMMENT_STATUS === 'success' ? 'موفقیت' : 'خطا',
                COMMENT_MESSAGE,
                COMMENT_STATUS
            );
        }
    });
})();