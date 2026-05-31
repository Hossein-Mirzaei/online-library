<?php
header('Content-Type: application/json; charset=utf-8');
include '../database/config.php';

$user_id = intval($_POST['user_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$user_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'پیام رو وارد کن']);
    exit();
}

$user_name = 'کاربر';
$u = $conn->query("SELECT first_name FROM users WHERE user_id = $user_id");
if ($u && $u->num_rows > 0) {
    $user_name = $u->fetch_assoc()['first_name'];
}

// ==========================================
// 1. دریافت تاریخچه چت‌های کاربر
// ==========================================
$chat_history = "";
$history_query = $conn->prepare("
    SELECT message, response, created_at 
    FROM ai_chats 
    WHERE user_id = ? 
    ORDER BY created_at ASC 
    LIMIT 20
");
$history_query->bind_param("i", $user_id);
$history_query->execute();
$history_result = $history_query->get_result();

$history_messages = [];
while ($row = $history_result->fetch_assoc()) {
    $history_messages[] = [
        'role' => 'user',
        'content' => $row['message']
    ];
    $history_messages[] = [
        'role' => 'assistant',
        'content' => $row['response']
    ];
}

// ==========================================
// 2. دریافت لیست کتاب‌ها
// ==========================================
$books_simple = "";
$books_query = $conn->query("
    SELECT b.book_id, b.name, b.author, b.price, c.category_name
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    ORDER BY b.name
");

while ($book = $books_query->fetch_assoc()) {
    $books_simple .= "ID:{$book['book_id']} | {$book['name']} | {$book['author']} | {$book['category_name']} | {$book['price']}T\n";
}

// ==========================================
// 3. ساخت prompt اصلی (system message)
// ==========================================
$system_prompt = "تو مشاور فروشگاه کتابی هستی. کاربر: {$user_name}

کتاب‌های موجود (فقط از این لیست معرفی کن):
{$books_simple}

قوانین:
- فقط از کتاب‌های لیست بالا معرفی کن (اگر کتاب مرتبط نبود صادقانه بگو)
- برای معرفی کتاب از این فرمت استفاده کن:
📖 نام کتاب - نویسنده | دسته | قیمت تومان
🔗 /new-web/user/book_details.php?id=X
- کوتاه و مفید جواب بده (حداکثر ۳-۴ خط)
- گرم و صمیمی باش ولی حاشیه نرو
- اگه کاربر درباره ادمین یا پشتیبانی سوال کرد، بگو از منوی سایت وارد صفحه «درباره ما» بشه
- اگه کاربر سلام کرد، سلام کن و آمادگیت رو برای کمک اعلام کن
- همیشه به تاریخچه مکالمه توجه کن و پاسخ‌های قبلی رو در نظر بگیر
- اگر کاربر به کتابی که قبلاً معرفی کردی اشاره کرد، می‌تونی دوباره همون کتاب رو معرفی کنی یا کتاب مشابه پیشنهاد بدی";

// ==========================================
// 4. ساخت آرایه messages برای API
// ==========================================
$messages = [
    ['role' => 'system', 'content' => $system_prompt]
];

// اضافه کردن تاریخچه چت‌ها (اگر وجود داشته باشه)
if (!empty($history_messages)) {
    $messages = array_merge($messages, $history_messages);
}

// اضافه کردن پیام جدید کاربر
$messages[] = ['role' => 'user', 'content' => $message];

// ==========================================
// 5. ارسال درخواست به API
// ==========================================
$ch = curl_init('https://api.gapgpt.app/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'gapgpt-qwen-3.5',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.5
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer sk-B3JbldTpv19a7IdFFpJle7j85XrXlz8y6wE4mQ8weoFnP4i8'
    ],
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ==========================================
// 6. پردازش پاسخ و ذخیره در دیتابیس
// ==========================================
if ($http_code === 200 && $response) {
    $result = json_decode($response, true);
    $ai_response = $result['choices'][0]['message']['content'] ?? '';
    
    if ($ai_response) {
        // تبدیل لینک‌ها به HTML
        $ai_response = preg_replace_callback(
            '/🔗\s*\/new-web\/user\/book_details\.php\?id=(\d+)/',
            function($m) {
                return "<a href='/new-web/user/book_details.php?id={$m[1]}' target='_self' style='color:#fbbf24;text-decoration:none;font-weight:bold;border-bottom:1px dashed #fbbf24;'>📖 مشاهده کتاب</a>";
            },
            $ai_response
        );
        
        // ذخیره چت در دیتابیس
        $stmt = $conn->prepare("INSERT INTO ai_chats (user_id, message, response) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $message, $ai_response);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'response' => $ai_response]);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => '🛠️ خطا در ارتباط با هوش مصنوعی. لطفاً دوباره تلاش کن.']);