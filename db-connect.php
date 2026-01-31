<?php
// ===================================
// ملف الاتصال بقاعدة البيانات
// ===================================

// تعريف الثوابت
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'my_order_db');

// تحديد رأس الاستجابة
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS المسبقة
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// محاولة الاتصال بقاعدة البيانات
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // التحقق من الاتصال
    if ($conn->connect_error) {
        throw new Exception("خطأ في الاتصال: " . $conn->connect_error);
    }
    
    // تحديد طريقة الترميز
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // معالجة الأخطاء
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage()
    ]);
    exit();
}

// دالة لتنظيف البيانات المدخلة
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

// دالة لإرسال استجابة JSON
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

?>
