<?php
// ===================================
// API المستخدمين والتسجيل
// ===================================

require_once 'db-connect.php';

// بدء جلسة العمل
session_start();

// التسجيل (إنشاء حساب جديد)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($password)) {
        sendResponse(false, "الحقول المطلوبة: الاسم، البريد الإلكتروني، وكلمة المرور", null, 400);
    }
    
    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "البريد الإلكتروني غير صحيح", null, 400);
    }
    
    // التحقق من وجود البريد الإلكتروني
    $checkEmail = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);
    
    if ($result->num_rows > 0) {
        sendResponse(false, "هذا البريد الإلكتروني مستخدم بالفعل", null, 409);
    }
    
    // تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // إدراج المستخدم الجديد
    $query = "INSERT INTO users (name, email, password, phone, address) 
              VALUES ('$name', '$email', '$hashedPassword', '$phone', '$address')";
    
    if ($conn->query($query)) {
        $userId = $conn->insert_id;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['is_admin'] = false;
        
        sendResponse(true, "تم التسجيل بنجاح", [
            'user_id' => $userId,
            'name' => $name,
            'email' => $email
        ], 201);
    } else {
        sendResponse(false, "خطأ في التسجيل: " . $conn->error, null, 500);
    }
}

// تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendResponse(false, "البريد الإلكتروني وكلمة المرور مطلوبة", null, 400);
    }
    
    // البحث عن المستخدم
    $query = "SELECT id, name, email, password, is_admin FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        sendResponse(false, "البريد الإلكتروني غير مسجل", null, 401);
    }
    
    $user = $result->fetch_assoc();
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        sendResponse(false, "كلمة المرور غير صحيحة", null, 401);
    }
    
    // تعيين بيانات الجلسة
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    
    sendResponse(true, "تم تسجيل الدخول بنجاح", [
        'user_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => $user['is_admin']
    ]);
}

// تسجيل الخروج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    sendResponse(true, "تم تسجيل الخروج بنجاح");
}

// الحصول على بيانات المستخدم الحالي
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'current') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "المستخدم غير مسجل دخول", null, 401);
    }
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT id, name, email, phone, address, is_admin, created_at FROM users WHERE id = $userId";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        sendResponse(true, "تم جلب بيانات المستخدم", $user);
    } else {
        sendResponse(false, "المستخدم غير موجود", null, 404);
    }
}

// تحديث بيانات المستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "المستخدم غير مسجل دخول", null, 401);
    }
    
    $userId = $_SESSION['user_id'];
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    $updates = [];
    if (!empty($name)) {
        $updates[] = "name = '$name'";
        $_SESSION['user_name'] = $name;
    }
    if (!empty($phone)) $updates[] = "phone = '$phone'";
    if (!empty($address)) $updates[] = "address = '$address'";
    
    if (empty($updates)) {
        sendResponse(false, "لا توجد بيانات للتحديث", null, 400);
    }
    
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = $userId";
    
    if ($conn->query($query)) {
        sendResponse(true, "تم تحديث البيانات بنجاح");
    } else {
        sendResponse(false, "خطأ في تحديث البيانات", null, 500);
    }
}

// تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, "المستخدم غير مسجل دخول", null, 401);
    }
    
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        sendResponse(false, "كلمة المرور الحالية والجديدة مطلوبة", null, 400);
    }
    
    // التحقق من كلمة المرور الحالية
    $query = "SELECT password FROM users WHERE id = $userId";
    $result = $conn->query($query);
    $user = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $user['password'])) {
        sendResponse(false, "كلمة المرور الحالية غير صحيحة", null, 401);
    }
    
    // تحديث كلمة المرور
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
    
    if ($conn->query($updateQuery)) {
        sendResponse(true, "تم تغيير كلمة المرور بنجاح");
    } else {
        sendResponse(false, "خطأ في تغيير كلمة المرور", null, 500);
    }
}

sendResponse(false, "الطلب غير مدعوم", null, 400);
?>
