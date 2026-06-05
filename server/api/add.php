<?php
// add.php - دریافت اطلاعات از Request Body (JSON)

// تنظیم هدرها
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// مدیریت درخواست OPTIONS (برای CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// نام فایل JSON
$jsonFile = 'data.json';

// تابع برای خواندن اطلاعات از فایل JSON
function readData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// تابع برای نوشتن اطلاعات در فایل JSON
function writeData($file, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($file, $jsonData);
}

// دریافت اطلاعات از Request Body
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

// پردازش درخواست POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // بررسی وجود داده
    if (!$inputData) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'بدنه درخواست معتبر نیست (JSON نامعتبر)',
            'received' => $rawInput
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // دریافت فیلدها
    $name = trim($inputData['name'] ?? '');
    $pass = trim($inputData['pass'] ?? '');
    $text = trim($inputData['text'] ?? '');
    
    // اعتبارسنجی
    if (empty($name) || empty($pass)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'فیلدهای name و pass اجباری هستند',
            'received' => $inputData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // خواندن اطلاعات موجود
    $data = readData($jsonFile);
    
    // ایجاد رکورد جدید
    $newRecord = [
        'id' => count($data) + 1,
        'name' => $name,
        'pass' => $pass,
        'text' => $text,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // اضافه کردن به آرایه
    $data[] = $newRecord;
    
    // ذخیره در فایل JSON
    if (writeData($jsonFile, $data)) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'اطلاعات با موفقیت ذخیره شد',
            'data' => $newRecord,
            'total_records' => count($data)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'خطا در ذخیره سازی فایل JSON'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// نمایش اطلاعات برای درخواست GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = readData($jsonFile);
    echo json_encode([
        'success' => true,
        'total' => count($data),
        'data' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// متدهای دیگر پشتیبانی نمی‌شوند
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'متد مجاز نیست. فقط POST و GET پشتیبانی می‌شوند'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>