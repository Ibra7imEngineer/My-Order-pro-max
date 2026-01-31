<?php
// ===================================
// API المنتجات
// ===================================

require_once 'db-connect.php';

// الحصول على جميع المنتجات أو منتج محدد
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // إذا كان هناك معرف منتج محدد
    if (isset($_GET['id'])) {
        $id = sanitize($_GET['id']);
        $query = "SELECT * FROM products WHERE id = $id AND is_available = TRUE";
        
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            sendResponse(true, "المنتج تم جلبه بنجاح", $product);
        } else {
            sendResponse(false, "المنتج غير موجود", null, 404);
        }
    }
    
    // الحصول على المنتجات حسب الفئة
    if (isset($_GET['category'])) {
        $category = sanitize($_GET['category']);
        $query = "SELECT * FROM products WHERE category = '$category' AND is_available = TRUE ORDER BY created_at DESC";
    } else {
        // الحصول على جميع المنتجات
        $query = "SELECT * FROM products WHERE is_available = TRUE ORDER BY created_at DESC";
    }
    
    $result = $conn->query($query);
    if ($result) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        sendResponse(true, "تم جلب المنتجات بنجاح", $products);
    } else {
        sendResponse(false, "خطأ في جلب المنتجات", null, 500);
    }
}

// إضافة منتج جديد (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // التحقق من أن المستخدم إدمن (يجب فحص الجلسة من جانب الكلاينت)
    
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = sanitize($_POST['category'] ?? '');
    $image_url = sanitize($_POST['image_url'] ?? '');
    
    if (empty($name) || empty($price) || empty($category)) {
        sendResponse(false, "الحقول المطلوبة: الاسم، السعر، والفئة", null, 400);
    }
    
    $query = "INSERT INTO products (name, description, price, category, image_url) 
              VALUES ('$name', '$description', $price, '$category', '$image_url')";
    
    if ($conn->query($query)) {
        $newId = $conn->insert_id;
        sendResponse(true, "تم إضافة المنتج بنجاح", ['id' => $newId], 201);
    } else {
        sendResponse(false, "خطأ في إضافة المنتج: " . $conn->error, null, 500);
    }
}

// تحديث منتج (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update')) {
    // قراءة البيانات المرسلة
    parse_str(file_get_contents("php://input"), $put_data);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $put_data = $_POST;
    }
    
    $id = sanitize($put_data['id'] ?? '');
    $name = sanitize($put_data['name'] ?? '');
    $description = sanitize($put_data['description'] ?? '');
    $price = floatval($put_data['price'] ?? 0);
    $category = sanitize($put_data['category'] ?? '');
    $image_url = sanitize($put_data['image_url'] ?? '');
    
    if (empty($id)) {
        sendResponse(false, "معرف المنتج مطلوب", null, 400);
    }
    
    $updates = [];
    if (!empty($name)) $updates[] = "name = '$name'";
    if (!empty($description)) $updates[] = "description = '$description'";
    if ($price > 0) $updates[] = "price = $price";
    if (!empty($category)) $updates[] = "category = '$category'";
    if (!empty($image_url)) $updates[] = "image_url = '$image_url'";
    
    if (empty($updates)) {
        sendResponse(false, "لا توجد بيانات للتحديث", null, 400);
    }
    
    $updateQuery = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = $id";
    
    if ($conn->query($updateQuery)) {
        sendResponse(true, "تم تحديث المنتج بنجاح");
    } else {
        sendResponse(false, "خطأ في تحديث المنتج: " . $conn->error, null, 500);
    }
}

// حذف منتج (للإدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete')) {
    parse_str(file_get_contents("php://input"), $delete_data);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $delete_data = $_POST;
    }
    
    $id = sanitize($delete_data['id'] ?? '');
    
    if (empty($id)) {
        sendResponse(false, "معرف المنتج مطلوب", null, 400);
    }
    
    // تحديث حالة التوفر بدلاً من الحذف الفعلي (أفضل لقاعدة البيانات)
    $query = "UPDATE products SET is_available = FALSE WHERE id = $id";
    
    if ($conn->query($query)) {
        sendResponse(true, "تم حذف المنتج بنجاح");
    } else {
        sendResponse(false, "خطأ في حذف المنتج: " . $conn->error, null, 500);
    }
}

// البحث عن المنتجات
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query = "SELECT * FROM products 
              WHERE is_available = TRUE 
              AND (name LIKE '%$search%' OR description LIKE '%$search%') 
              ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    if ($result) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        sendResponse(true, "تم جلب نتائج البحث", $products);
    } else {
        sendResponse(false, "خطأ في البحث", null, 500);
    }
}

sendResponse(false, "الطلب غير مدعوم", null, 400);
?>
