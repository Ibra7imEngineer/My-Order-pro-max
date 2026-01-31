<?php
// ===================================
// ملف الإعدادات العام
// ===================================

// معلومات المتجر
define('STORE_NAME', 'My Order');
define('STORE_PHONE', '+201021279663');
define('STORE_EMAIL', 'admin@myorder.com');
define('STORE_ADDRESS', 'القاهرة، مصر');

// الإعدادات الأخرى
define('SHIPPING_FEE', 20); // رسوم التوصيل الثابتة
define('CURRENCY', 'ج.م');  // العملة
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // حد أقصى لحجم الملف (5MB)

// التاريخ والوقت
date_default_timezone_set('Africa/Cairo');

// وضع التصحيح (Debug Mode)
define('DEBUG', true); // غير إلى false في الإنتاج

// معلومات الصور
define('IMAGES_FOLDER', 'images/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// أيقونات الفئات
$categories = [
    'food' => ['name' => 'أطعمة', 'icon' => '🍔'],
    'drinks' => ['name' => 'مشروبات', 'icon' => '🥤'],
    'sweets' => ['name' => 'حلويات', 'icon' => '🍰']
];

// حالات الطلب
$order_statuses = [
    'جديد' => 'New',
    'قيد المعالجة' => 'Processing',
    'في الطريق' => 'On the way',
    'تم التسليم' => 'Delivered',
    'ملغاة' => 'Cancelled'
];

?>
