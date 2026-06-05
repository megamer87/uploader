<?php
// edit.php - ویرایش اطلاعات در فایل JSON با استفاده از name و pass

// تنظیم هدرها
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
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

// پردازش درخواست PUT یا POST (برای ویرایش)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'edit')) {
    
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
    $newData = $inputData['data'] ?? [];
    
    // اعتبارسنجی
    if (empty($name) || empty($pass)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'فیلدهای name و pass برای پیدا کردن رکورد اجباری هستند',
            'received' => $inputData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // بررسی وجود newData برای ویرایش
    if (empty($newData) || !is_array($newData)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'فیلد data باید یک آرایه شامل فیلدهای قابل ویرایش باشد',
            'example' => [
                'name' => 'http://localhost/edit.php',
                'method' => 'PUT',
                'body' => [
                    'name' => 'نام قدیم',
                    'pass' => 'رمز قدیم',
                    'data' => [
                        'name' => 'نام جدید',
                        'pass' => 'رمز جدید',
                        'text' => 'متن جدید'
                    ]
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // خواندن اطلاعات موجود
    $data = readData($jsonFile);
    $originalCount = count($data);
    
    // جستجو و ویرایش رکورد
    $found = false;
    $updatedRecord = null;
    $oldRecord = null;
    
    foreach ($data as $index => &$record) {
        if ($record['name'] === $name && $record['pass'] === $pass) {
            $found = true;
            $oldRecord = $record;
            
            // اعمال تغییرات - فقط فیلدهایی که در newData ارسال شده‌اند
            if (isset($newData['name'])) {
                $record['name'] = trim($newData['name']);
            }
            if (isset($newData['pass'])) {
                $record['pass'] = trim($newData['pass']);
            }
            if (isset($newData['text'])) {
                $record['text'] = trim($newData['text']);
            }
            
            // اضافه کردن زمان ویرایش
            $record['updated_at'] = date('Y-m-d H:i:s');
            
            $updatedRecord = $record;
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'رکوردی با این نام و رمز پیدا نشد',
            'search' => ['name' => $name, 'pass' => $pass],
            'total_records_checked' => $originalCount
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // ذخیره در فایل JSON
    if (writeData($jsonFile, $data)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'رکورد با موفقیت ویرایش شد',
            'old_data' => $oldRecord,
            'new_data' => $updatedRecord,
            'changes' => $newData
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

// متدهای دیگر پشتیبانی نمی‌شوند
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'متد مجاز نیست. متدهای مجاز: PUT, POST (با ?action=edit)'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>