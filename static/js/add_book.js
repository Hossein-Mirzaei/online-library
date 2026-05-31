

document.addEventListener('DOMContentLoaded', function() {
    
    const fileInput = document.getElementById('bookImage');
    const fileName = document.getElementById('fileName');
    const previewContainer = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                fileName.innerHTML = '<span>📁</span> ' + this.files[0].name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                fileName.innerHTML = '<span>📁</span> هیچ فایلی انتخاب نشده است';
                previewContainer.style.display = 'none';
            }
        });
    }
    
    const form = document.getElementById('addBookForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const yearInput = document.getElementById('publicationYear');
            const priceInput = document.getElementById('price');
            const fileInput = document.getElementById('bookImage');
            
            if (yearInput.value < 1000 || yearInput.value > 2100) {
                e.preventDefault();
                alert('❌ سال انتشار باید بین ۱۰۰۰ تا ۲۱۰۰ باشد.');
                yearInput.focus();
                return false;
            }
            
            if (priceInput.value < 0) {
                e.preventDefault();
                alert('❌ قیمت نمی‌تواند منفی باشد.');
                priceInput.focus();
                return false;
            }
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('❌ فرمت تصویر باید JPG، PNG یا GIF باشد.');
                    return false;
                }
                
                if (file.size > 2 * 1024 * 1024) {
                    e.preventDefault();
                    alert('❌ حجم تصویر نباید بیشتر از ۲ مگابایت باشد.');
                    return false;
                }
            }
        });
    }
});

function resetForm() {
    document.getElementById('addBookForm').reset();
    document.getElementById('fileName').innerHTML = '<span>📁</span> هیچ فایلی انتخاب نشده است';
    document.getElementById('imagePreview').style.display = 'none';
}

const style = document.createElement('style');
style.textContent = `
    .file-input {
        display: none;
    }
    
    .file-input-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .file-input-label:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .file-name {
        margin-right: 10px;
        color: #666;
        font-size: 14px;
    }
    
    .message {
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.3s ease;
    }
    
    .message.success {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #22c55e;
    }
    
    .message.error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .image-preview-container {
        text-align: center;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        border: 1px dashed rgba(255, 255, 255, 0.2);
    }
`;
document.head.appendChild(style);
