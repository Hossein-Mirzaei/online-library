(function() {
    'use strict';
    
    const chatTrigger = document.getElementById('chatTrigger');
    const chatDrawer = document.getElementById('chatDrawer');
    const chatOverlay = document.getElementById('chatOverlay');
    const chatClose = document.getElementById('chatDrawerClose');
    const chatMessages = document.getElementById('chatMessages');
    const chatWelcome = document.getElementById('chatWelcome');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const chatClearBtn = document.getElementById('chatClearBtn');
    
    let isLoading = false;
    
    function openChat() {
        chatDrawer.classList.add('open');
        chatOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        loadChatHistory();
    }
    
    function closeChat() {
        chatDrawer.classList.remove('open');
        chatOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    async function loadChatHistory() {
        try {
            const response = await fetch(`../api/chat_history.php?user_id=${window.CHAT_USER_ID}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                chatMessages.innerHTML = '';
                data.messages.forEach(msg => {
                    addMessage('user', msg.message, msg.created_at, false);
                    addMessage('bot', msg.response, msg.created_at, true);
                });
                chatWelcome.style.display = 'none';
                scrollToBottom();
            } else {
                chatMessages.innerHTML = '';
                chatWelcome.style.display = 'block';
            }
        } catch {
            chatMessages.innerHTML = '<div class="chat-loading">خطا در بارگذاری</div>';
        }
    }
    
    function addMessage(type, content, time = null, isHtml = false) {
        const div = document.createElement('div');
        div.className = `chat-message ${type}`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.textContent = type === 'user' ? '👤' : '🤖';
        
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        
        if (isHtml) {
            let cleanContent = content;
            bubble.innerHTML = cleanContent;
        } else {
            bubble.textContent = content;
        }
        
        if (time) {
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const date = new Date(time);
            if (!isNaN(date.getTime())) {
                timeDiv.textContent = date.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
            }
            bubble.appendChild(timeDiv);
        }
        
        div.appendChild(avatar);
        div.appendChild(bubble);
        chatMessages.appendChild(div);
        scrollToBottom();
    }
    
    function showTyping() {
        const div = document.createElement('div');
        div.className = 'chat-message bot';
        div.id = 'typingIndicator';
        div.innerHTML = `<div class="message-avatar">🤖</div><div class="message-bubble"><div class="typing-indicator"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div></div>`;
        chatMessages.appendChild(div);
        scrollToBottom();
    }
    
    function hideTyping() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }
    
    function scrollToBottom() {
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || isLoading) return;
        
        chatWelcome.style.display = 'none';
        addMessage('user', message, null, false);
        chatInput.value = '';
        
        isLoading = true;
        sendBtn.disabled = true;
        chatInput.disabled = true;
        showTyping();
        
        try {
            const response = await fetch('../api/chat_advisor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${window.CHAT_USER_ID}&message=${encodeURIComponent(message)}`
            });
            const data = await response.json();
            hideTyping();
            
            if (data.success) {
                addMessage('bot', data.response, null, true);
            } else {
                addMessage('bot', '🛠️ ' + (data.message || 'سرویس مشاوره در دسترس نیست.'), null, false);
            }
        } catch {
            hideTyping();
            addMessage('bot', '🌐 خطا در ارتباط با سرور.', null, false);
        } finally {
            isLoading = false;
            sendBtn.disabled = false;
            chatInput.disabled = false;
            chatInput.focus();
        }
    }
    
    async function clearChatHistory() {
        if (!chatClearBtn || chatClearBtn.disabled) return;
        
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        `;
        
        overlay.innerHTML = `
            <div style="
                background: linear-gradient(135deg, rgba(15,18,25,0.98), rgba(22,26,35,0.98));
                border-radius: 16px;
                width: 90%;
                max-width: 340px;
                padding: 1.5rem;
                text-align: center;
                border: 1px solid rgba(239,68,68,0.3);
                box-shadow: 0 20px 40px rgba(0,0,0,0.5);
                animation: scaleIn 0.2s ease;
            ">
                <div style="
                    width: 60px;
                    height: 60px;
                    margin: 0 auto 1rem;
                    border-radius: 50%;
                    background: rgba(239,68,68,0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.8rem;
                ">🗑️</div>
                <h3 style="
                    color: #ef4444;
                    font-size: 1.1rem;
                    font-weight: bold;
                    margin-bottom: 0.5rem;
                    font-family: 'Vazirmatn', system-ui, sans-serif;
                ">پاک کردن تاریخچه</h3>
                <p style="
                    color: rgba(255,255,255,0.75);
                    font-size: 0.85rem;
                    margin-bottom: 1.25rem;
                    line-height: 1.6;
                    font-family: 'Vazirmatn', system-ui, sans-serif;
                ">آیا از پاک کردن تمام تاریخچه چت مطمئن هستید؟<br>این کار قابل بازگشت نیست!</p>
                <div style="display: flex; gap: 0.75rem;">
                    <button id="confirmNo" style="
                        flex: 1;
                        padding: 0.7rem;
                        background: rgba(255,255,255,0.08);
                        border: 1px solid rgba(255,255,255,0.15);
                        border-radius: 10px;
                        color: white;
                        cursor: pointer;
                        font-size: 0.85rem;
                        font-family: 'Vazirmatn', system-ui, sans-serif;
                        transition: all 0.2s;
                    ">انصراف</button>
                    <button id="confirmYes" style="
                        flex: 1;
                        padding: 0.7rem;
                        background: linear-gradient(135deg, #ef4444, #dc2626);
                        border: none;
                        border-radius: 10px;
                        color: white;
                        cursor: pointer;
                        font-size: 0.85rem;
                        font-weight: bold;
                        font-family: 'Vazirmatn', system-ui, sans-serif;
                        transition: all 0.2s;
                    ">بله، حذف کن</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        const removeDialog = () => {
            overlay.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => overlay.remove(), 200);
        };
        
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) removeDialog();
        });
        
        document.getElementById('confirmNo').addEventListener('click', removeDialog);
        
        document.getElementById('confirmYes').addEventListener('click', async function() {
            removeDialog();
            
            chatClearBtn.disabled = true;
            chatClearBtn.style.opacity = '0.5';
            
            try {
                const response = await fetch('../api/clear_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${window.CHAT_USER_ID}`
                });
                const data = await response.json();
                
                if (data.success) {
                    chatMessages.innerHTML = '';
                    chatWelcome.style.display = 'block';
                    showNotification('✅ تاریخچه چت با موفقیت پاک شد', 'success');
                } else {
                    showNotification('❌ ' + (data.message || 'خطا در پاک کردن تاریخچه'), 'error');
                }
            } catch {
                showNotification('❌ خطا در ارتباط با سرور', 'error');
            } finally {
                if (chatClearBtn) {
                    chatClearBtn.disabled = false;
                    chatClearBtn.style.opacity = '1';
                }
            }
        });
        
        const escHandler = function(e) {
            if (e.key === 'Escape') {
                removeDialog();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }
    
    function showNotification(message, type = 'info') {
        const oldNotif = document.querySelector('.chat-notification');
        if (oldNotif) oldNotif.remove();
        
        const notification = document.createElement('div');
        notification.className = `chat-notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            z-index: 9999;
            font-size: 0.85rem;
            font-family: "Vazirmatn", system-ui, sans-serif;
            animation: notifSlideIn 0.3s ease;
            background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
            color: white;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            pointer-events: none;
            white-space: nowrap;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'notifSlideOut 0.3s ease';
            notification.addEventListener('animationend', () => notification.remove());
        }, 3000);
    }
    
    window.sendSuggestion = (text) => {
        chatInput.value = text;
        sendMessage();
    };
    
    if (chatTrigger) chatTrigger.addEventListener('click', openChat);
    if (chatClose) chatClose.addEventListener('click', closeChat);
    if (chatOverlay) chatOverlay.addEventListener('click', closeChat);
    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    
    if (chatClearBtn) {
        chatClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearChatHistory();
        });
        
        chatClearBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearChatHistory();
        });
    }
    
    if (chatInput) {
        chatInput.addEventListener('keypress', (e) => { 
            if (e.key === 'Enter' && !isLoading) sendMessage(); 
        });
    }
    
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && chatDrawer && chatDrawer.classList.contains('open')) closeChat(); 
    });
    
    const notifStyles = document.createElement('style');
    notifStyles.textContent = `
        @keyframes notifSlideIn {
            from {
                transform: translateX(-50%) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }
        @keyframes notifSlideOut {
            from {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
            to {
                transform: translateX(-50%) translateY(-20px);
                opacity: 0;
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(notifStyles);
})();