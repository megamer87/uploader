<?php
// view.php - نمایش اطلاعات از فایل JSON با قابلیت فیلتر بر اساس name و pass

// تنظیم هدرها
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// دریافت اطلاعات از Request Body (برای درخواست POST)
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

// پردازش درخواست POST (نمایش با فیلتر)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // خواندن تمام اطلاعات
    $data = readData($jsonFile);
    
    // اگر داده‌ای ارسال شده، فیلتر کن
    if ($inputData && (isset($inputData['name']) || isset($inputData['pass']))) {
        $name = trim($inputData['name'] ?? '');
        $pass = trim($inputData['pass'] ?? '');
        
        $filteredData = [];
        
        foreach ($data as $record) {
            $match = true;
            
            if (!empty($name) && $record['name'] !== $name) {
                $match = false;
            }
            
            if (!empty($pass) && $record['pass'] !== $pass) {
                $match = false;
            }
            
            if ($match) {
                $filteredData[] = $record;
            }
        }
        
        echo json_encode([
            'success' => true,
            'total' => count($filteredData),
            'filters' => [
                'name' => $name ?: '(همه)',
                'pass' => $pass ?: '(همه)'
            ],
            'data' => $filteredData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // نمایش همه رکوردها
        echo json_encode([
            'success' => true,
            'total' => count($data),
            'filters' => null,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// پردازش درخواست GET (نمایش همه یا بر اساس پارامترهای URL)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = readData($jsonFile);
    
    // فیلتر بر اساس پارامترهای URL (مثلاً ?name=علی&pass=123)
    $name = $_GET['name'] ?? '';
    $pass = $_GET['pass'] ?? '';
    
    if (!empty($name) || !empty($pass)) {
        $filteredData = [];
        
        foreach ($data as $record) {
            $match = true;
            
            if (!empty($name) && $record['name'] !== $name) {
                $match = false;
            }
            
            if (!empty($pass) && $record['pass'] !== $pass) {
                $match = false;
            }
            
            if ($match) {
                $filteredData[] = $record;
            }
        }
        
        echo json_encode([
            'success' => true,
            'total' => count($filteredData),
            'filters' => [
                'name' => $name ?: '(همه)',
                'pass' => $pass ?: '(همه)'
            ],
            'data' => $filteredData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // نمایش همه رکوردها
        echo json_encode([
            'success' => true,
            'total' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// متدهای دیگر پشتیبانی نمی‌شوند
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'متد مجاز نیست. فقط GET و POST پشتیبانی می‌شوند'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>