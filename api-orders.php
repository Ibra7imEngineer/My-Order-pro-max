<?php
// ===================================
// API الطلبات
// ===================================

require_once 'db-connect.php';
session_start();

// التحقق من تسجيل دخول المستخدم
function checkUserLogin() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "المستخدم غير مسجل دخول", null, 401);
    }
    return $_SESSION['user_id'];
}

// إنشاء طلب جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $userId = checkUserLogin();
    
    $items = json_decode($_POST['items'] ?? '[]', true);
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $delivery_address = sanitize($_POST['delivery_address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($items) || !is_array($items)) {
        sendResponse(false, "لا توجد عناصر في الطلب", null, 400);
    }
    
    if (empty($delivery_address)) {
        sendResponse(false, "عنوان التسليم مطلوب", null, 400);
    }
    
    // حساب السعر الإجمالي
    $totalPrice = 0;
    $shippingFee = 20;
    
    // إدراج الطلب
    $query = "INSERT INTO orders (user_id, total_price, shipping_fee, payment_method, delivery_address, notes, order_status) 
              VALUES ($userId, 0, $shippingFee, '$payment_method', '$delivery_address', '$notes', 'جديد')";
    
    if (!$conn->query($query)) {
        sendResponse(false, "خطأ في إنشاء الطلب: " . $conn->error, null, 500);
    }
    
    $orderId = $conn->insert_id;
    
    // إضافة عناصر الطلب
    foreach ($items as $item) {
        $productId = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }
        
        $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                      VALUES ($orderId, $productId, $quantity, $price)";
        
        $conn->query($itemQuery);
        $totalPrice += ($price * $quantity);
    }
    
    // تحديث السعر الإجمالي للطلب
    $finalTotal = $totalPrice + $shippingFee;
    $updateQuery = "UPDATE orders SET total_price = $finalTotal WHERE id = $orderId";
    $conn->query($updateQuery);
    
    sendResponse(true, "تم إنشاء الطلب بنجاح", [
        'order_id' => $orderId,
        'total_price' => $finalTotal,
        'shipping_fee' => $shippingFee
    ], 201);
}

// جلب طلبات المستخدم
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'my_orders') {
    $userId = checkUserLogin();
    
    $query = "SELECT o.id, o.total_price, o.shipping_fee, o.payment_method, o.order_status, o.created_at 
              FROM orders o 
              WHERE o.user_id = $userId 
              ORDER BY o.created_at DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        sendResponse(false, "خطأ في جلب الطلبات", null, 500);
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    sendResponse(true, "تم جلب الطلبات بنجاح", $orders);
}

// جلب تفاصيل طلب محدد
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'order_details') {
    $userId = checkUserLogin();
    $orderId = intval($_GET['id'] ?? 0);
    
    if ($orderId <= 0) {
        sendResponse(false, "معرف الطلب مطلوب", null, 400);
    }
    
    // التحقق من أن الطلب ينتمي للمستخدم
    $checkQuery = "SELECT id FROM orders WHERE id = $orderId AND user_id = $userId";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows === 0) {
        sendResponse(false, "الطلب غير موجود", null, 404);
    }
    
    // جلب تفاصيل الطلب
    $query = "SELECT o.id, o.total_price, o.shipping_fee, o.payment_method, o.order_status, o.delivery_address, o.notes, o.created_at 
              FROM orders o 
              WHERE o.id = $orderId";
    
    $orderResult = $conn->query($query);
    $order = $orderResult->fetch_assoc();
    
    // جلب عناصر الطلب
    $itemsQuery = "SELECT oi.id, p.name, p.image_url, oi.quantity, oi.price, (oi.quantity * oi.price) as total 
                   FROM order_items oi 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = $orderId";
    
    $itemsResult = $conn->query($itemsQuery);
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = $row;
    }
    
    $order['items'] = $items;
    
    sendResponse(true, "تم جلب تفاصيل الطلب", $order);
}

// إلغاء طلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $userId = checkUserLogin();
    $orderId = intval($_POST['id'] ?? 0);
    
    if ($orderId <= 0) {
        sendResponse(false, "معرف الطلب مطلوب", null, 400);
    }
    
    // التحقق من أن الطلب ينتمي للمستخدم
    $checkQuery = "SELECT order_status FROM orders WHERE id = $orderId AND user_id = $userId";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows === 0) {
        sendResponse(false, "الطلب غير موجود", null, 404);
    }
    
    $order = $checkResult->fetch_assoc();
    if ($order['order_status'] !== 'جديد') {
        sendResponse(false, "لا يمكن إلغاء الطلب في هذه الحالة", null, 400);
    }
    
    // تحديث حالة الطلب
    $updateQuery = "UPDATE orders SET order_status = 'ملغاة' WHERE id = $orderId";
    
    if ($conn->query($updateQuery)) {
        sendResponse(true, "تم إلغاء الطلب بنجاح");
    } else {
        sendResponse(false, "خطأ في إلغاء الطلب", null, 500);
    }
}

// جلب جميع الطلبات (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'all_orders') {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        sendResponse(false, "ليس لديك صلاحية", null, 403);
    }
    
    $query = "SELECT o.id, o.user_id, u.name, u.phone, o.total_price, o.order_status, o.created_at 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              ORDER BY o.created_at DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        sendResponse(false, "خطأ في جلب الطلبات", null, 500);
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    sendResponse(true, "تم جلب الطلبات", $orders);
}

// تحديث حالة الطلب (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        sendResponse(false, "ليس لديك صلاحية", null, 403);
    }
    
    $orderId = intval($_POST['id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    
    if ($orderId <= 0 || empty($status)) {
        sendResponse(false, "معرف الطلب والحالة مطلوبة", null, 400);
    }
    
    $validStatuses = ['جديد', 'قيد المعالجة', 'في الطريق', 'تم التسليم', 'ملغاة'];
    if (!in_array($status, $validStatuses)) {
        sendResponse(false, "حالة الطلب غير صحيحة", null, 400);
    }
    
    $updateQuery = "UPDATE orders SET order_status = '$status' WHERE id = $orderId";
    
    if ($conn->query($updateQuery)) {
        sendResponse(true, "تم تحديث حالة الطلب بنجاح");
    } else {
        sendResponse(false, "خطأ في تحديث الحالة", null, 500);
    }
}

sendResponse(false, "الطلب غير مدعوم", null, 400);
?>
