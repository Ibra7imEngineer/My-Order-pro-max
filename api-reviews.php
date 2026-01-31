<?php
// ===================================
// API التقييمات والآراء
// ===================================

require_once 'db-connect.php';
session_start();

// إضافة تقييم لمنتج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "المستخدم غير مسجل دخول", null, 401);
    }
    
    $userId = $_SESSION['user_id'];
    $productId = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    
    if ($productId <= 0 || $rating < 1 || $rating > 5) {
        sendResponse(false, "معرف المنتج والتقييم (1-5) مطلوبة", null, 400);
    }
    
    // التحقق من وجود المنتج
    $productCheck = "SELECT id FROM products WHERE id = $productId";
    $result = $conn->query($productCheck);
    
    if ($result->num_rows === 0) {
        sendResponse(false, "المنتج غير موجود", null, 404);
    }
    
    // التحقق من وجود تقييم سابق
    $existingReview = "SELECT id FROM reviews WHERE user_id = $userId AND product_id = $productId";
    $existingResult = $conn->query($existingReview);
    
    if ($existingResult->num_rows > 0) {
        // تحديث التقييم السابق
        $updateQuery = "UPDATE reviews SET rating = $rating, comment = '$comment' WHERE user_id = $userId AND product_id = $productId";
        if ($conn->query($updateQuery)) {
            sendResponse(true, "تم تحديث التقييم بنجاح");
        } else {
            sendResponse(false, "خطأ في تحديث التقييم", null, 500);
        }
    } else {
        // إضافة تقييم جديد
        $insertQuery = "INSERT INTO reviews (user_id, product_id, rating, comment) 
                        VALUES ($userId, $productId, $rating, '$comment')";
        
        if ($conn->query($insertQuery)) {
            sendResponse(true, "تم إضافة التقييم بنجاح", ['review_id' => $conn->insert_id], 201);
        } else {
            sendResponse(false, "خطأ في إضافة التقييم", null, 500);
        }
    }
}

// جلب تقييمات منتج
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'product_reviews') {
    $productId = intval($_GET['product_id'] ?? 0);
    
    if ($productId <= 0) {
        sendResponse(false, "معرف المنتج مطلوب", null, 400);
    }
    
    $query = "SELECT r.id, r.rating, r.comment, r.created_at, u.name, u.id as user_id 
              FROM reviews r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.product_id = $productId 
              ORDER BY r.created_at DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        sendResponse(false, "خطأ في جلب التقييمات", null, 500);
    }
    
    $reviews = [];
    $totalRating = 0;
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
        $totalRating += $row['rating'];
        $count++;
    }
    
    $avgRating = $count > 0 ? round($totalRating / $count, 1) : 0;
    
    sendResponse(true, "تم جلب التقييمات", [
        'reviews' => $reviews,
        'average_rating' => $avgRating,
        'total_reviews' => $count
    ]);
}

// إضافة رسالة تواصل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // إذا كان المستخدم مسجل دخول، استخدم بيانات حسابه
    if ($userId) {
        $userQuery = "SELECT name, email FROM users WHERE id = $userId";
        $userResult = $conn->query($userQuery);
        $user = $userResult->fetch_assoc();
        $name = $user['name'];
        $email = $user['email'];
    }
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        sendResponse(false, "جميع الحقول مطلوبة", null, 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "البريد الإلكتروني غير صحيح", null, 400);
    }
    
    $query = "INSERT INTO contact_messages (user_id, name, email, subject, message) 
              VALUES (" . ($userId ? $userId : 'NULL') . ", '$name', '$email', '$subject', '$message')";
    
    if ($conn->query($query)) {
        sendResponse(true, "تم إرسال الرسالة بنجاح", ['message_id' => $conn->insert_id], 201);
    } else {
        sendResponse(false, "خطأ في إرسال الرسالة", null, 500);
    }
}

// جلب جميع رسائل التواصل (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'all_messages') {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        sendResponse(false, "ليس لديك صلاحية", null, 403);
    }
    
    $query = "SELECT id, name, email, subject, message, is_read, created_at 
              FROM contact_messages 
              ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        sendResponse(false, "خطأ في جلب الرسائل", null, 500);
    }
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    sendResponse(true, "تم جلب الرسائل", $messages);
}

// جلب الآراء والتقييمات من جميع المستخدمين
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'all_reviews') {
    $query = "SELECT r.id, r.rating, r.comment, r.created_at, u.name, p.name as product_name 
              FROM reviews r 
              JOIN users u ON r.user_id = u.id 
              JOIN products p ON r.product_id = p.id 
              ORDER BY r.created_at DESC";
    
    $result = $conn->query($query);
    if (!$result) {
        sendResponse(false, "خطأ في جلب التقييمات", null, 500);
    }
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    sendResponse(true, "تم جلب التقييمات", $reviews);
}

sendResponse(false, "الطلب غير مدعوم", null, 400);
?>
