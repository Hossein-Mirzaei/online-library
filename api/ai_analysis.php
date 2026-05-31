<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../database/config.php';

if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    echo json_encode(['success' => false, 'message' => 'شناسه کتاب نامعتبر است.']);
    exit();
}

$book_id = intval($_GET['book_id']);

$book_stmt = $conn->prepare("SELECT name, author FROM books WHERE book_id = ?");
$book_stmt->bind_param("i", $book_id);
$book_stmt->execute();
$book_result = $book_stmt->get_result();
$book = $book_result->fetch_assoc();
$book_stmt->close();

if (!$book) {
    echo json_encode(['success' => false, 'message' => 'کتاب مورد نظر یافت نشد.']);
    exit();
}

$comments_query = $conn->prepare("
    SELECT c.rating, c.comment, c.created_at,
           u.first_name, u.last_name
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.book_id = ?
    ORDER BY c.created_at DESC
");
$comments_query->bind_param("i", $book_id);
$comments_query->execute();
$result = $comments_query->get_result();

$comments = [];
$total_rating = 0;
$rating_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
    $total_rating += $row['rating'];
    $rating_distribution[$row['rating']]++;
}
$comments_query->close();

$total_comments = count($comments);

if ($total_comments === 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'هنوز نظری برای این کتاب ثبت نشده است.',
        'total_comments' => 0
    ]);
    exit();
}

$avg_rating = round($total_rating / $total_comments, 1);
$positive_comments = $rating_distribution[4] + $rating_distribution[5];
$satisfaction_rate = round(($positive_comments / $total_comments) * 100);

$comments_details = "";
foreach ($comments as $index => $c) {
    $comments_details .= ($index + 1) . ". " . htmlspecialchars($c['first_name']) . " (امتیاز {$c['rating']}/۵): «{$c['comment']}»\n";
}

$prompt = "تو یک تحلیلگر حرفه‌ای نظرات کاربران یک فروشگاه کتاب هستی. لطفاً نظرات زیر را که درباره کتاب «{$book['name']}» نوشته «{$book['author']}» ثبت شده، با دقت تحلیل کن:

آمار کلی:
- تعداد کل نظرات: {$total_comments}
- میانگین امتیاز: {$avg_rating} از ۵
- توزیع امتیازات: ★★★★★ ({$rating_distribution[5]} نفر), ★★★★ ({$rating_distribution[4]} نفر), ★★★ ({$rating_distribution[3]} نفر), ★★ ({$rating_distribution[2]} نفر), ★ ({$rating_distribution[1]} نفر)
- درصد رضایت: {$satisfaction_rate}%

متن کامل نظرات:
{$comments_details}

لطفاً تحلیل خود را به صورت زیر و به زبان فارسی ارائه بده (حتماً بر اساس متن نظرات باشه، نه حدس و گمان):

📊 **خلاصه آمار:**
(میانگین امتیاز، درصد رضایت، تعداد نظرات)

✅ **نقاط قوت (بر اساس گفته کاربران):**
- (حداقل ۲ مورد از مزایایی که کاربران در نظراتشون گفتن، با ذکر مثال یا نقل قول کوتاه)
- ...

⚠️ **نقاط ضعف (بر اساس گفته کاربران):**
- (حداقل ۱ مورد از معایبی که کاربران گفتن، با ذکر مثال)
- ...

💡 **تحلیل و نظر نهایی:**
(یک پاراگراف کوتاه که جمع‌بندی کنه و بگه این کتاب برای چه کسانی مناسبه و آیا در کل توصیه میشه یا نه)

مهم: تحلیلت فقط بر اساس متن نظرات باشه. اگه کاربری مزیت یا عیبی نگفته، چیزی از خودت اضافه نکن.";

// تغییر API key و URL به gapgpt
$api_key = 'sk-B3JbldTpv19a7IdFFpJle7j85XrXlz8y6wE4mQ8weoFnP4i8';
$api_url = 'https://api.gapgpt.app/v1/chat/completions';

$data = [
    'model' => 'gapgpt-qwen-3.5',  // تغییر نام مدل
    'messages' => [
        [
            'role' => 'system',
            'content' => 'شما یک تحلیلگر حرفه‌ای نظرات کاربران هستید. لطفاً دقیقاً بر اساس متن نظرات تحلیل کنید و پاسخ خود را به زبان فارسی و با فرمت خواسته شده ارائه دهید.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 600,
    'temperature' => 0.5
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code === 200 && $response) {
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $analysis = $result['choices'][0]['message']['content'];
        
        echo json_encode([
            'success' => true,
            'analysis' => $analysis,
            'stats' => [
                'total_comments' => $total_comments,
                'avg_rating' => $avg_rating,
                'satisfaction_rate' => $satisfaction_rate,
                'distribution' => $rating_distribution
            ]
        ]);
        exit();
    }
}

echo json_encode([
    'success' => false,
    'message' => 'سرویس هوش مصنوعی در حال حاضر در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.',
    'error_details' => [
        'http_code' => $http_code,
        'curl_error' => $curl_error ?: null
    ]
]);

$conn->close();
?>