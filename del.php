<?php
// del.php - حذف اطلاعات از فایل JSON با استفاده از name و pass

// تنظیم هدرها
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, GET, OPTIONS');
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

// پردازش درخواست DELETE یا POST (برای حذف)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete')) {
    
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
    
    // اعتبارسنجی
    if (empty($name) || empty($pass)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'فیلدهای name و pass برای حذف اجباری هستند',
            'received' => $inputData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // خواندن اطلاعات موجود
    $data = readData($jsonFile);
    $originalCount = count($data);
    
    // جستجو و حذف رکوردهای matching
    $deletedRecords = [];
    $remainingData = [];
    
    foreach ($data as $record) {
        if ($record['name'] === $name && $record['pass'] === $pass) {
            $deletedRecords[] = $record;
        } else {
            $remainingData[] = $record;
        }
    }
    
    $deletedCount = count($deletedRecords);
    
    if ($deletedCount === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'رکوردی با این نام و رمز پیدا نشد',
            'search' => ['name' => $name, 'pass' => $pass],
            'total_records_checked' => $originalCount
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // بازنویسی IDها برای رکوردهای باقی‌مانده
    $remainingData = array_values($remainingData); // ریست ایندکس
    foreach ($remainingData as $index => &$record) {
        $record['id'] = $index + 1;
    }
    
    // ذخیره در فایل JSON
    if (writeData($jsonFile, $remainingData)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "$deletedCount رکورد با موفقیت حذف شد",
            'deleted_count' => $deletedCount,
            'deleted_records' => $deletedRecords,
            'remaining_count' => count($remainingData)
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

// حذف همه رکوردها (برای درخواست DELETE با پارامتر special)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['all']) && $_GET['all'] === 'true') {
    if (writeData($jsonFile, [])) {
        echo json_encode([
            'success' => true,
            'message' => 'همه رکوردها با موفقیت حذف شدند'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'خطا در حذف رکوردها'
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
    'error' => 'متد مجاز نیست. متدهای مجاز: POST, DELETE, GET'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>