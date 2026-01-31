<!-- ملاحظات تقنية - My Order -->

# 🔧 ملاحظات تقنية ومعمارية المشروع

## 1️⃣ معمارية النظام

```
┌─────────────────────────────────────────────────────┐
│                    المتصفح (Frontend)                │
│  ┌───────────────────────────────────────────────┐  │
│  │  index.html + style.css + script-new.php      │  │
│  │  (HTML, CSS, JavaScript)                      │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       │ (طلبات HTTP)
                       ▼
┌─────────────────────────────────────────────────────┐
│                   الخادم (Backend)                  │
│  ┌───────────────────────────────────────────────┐  │
│  │           Apache + PHP 7.4+                    │  │
│  ├───────────────────────────────────────────────┤  │
│  │  • db-connect.php (الاتصال بقاعدة البيانات)   │  │
│  │  • api-products.php (إدارة المنتجات)         │  │
│  │  • api-users.php (إدارة المستخدمين)         │  │
│  │  • api-orders.php (إدارة الطلبات)            │  │
│  │  • api-reviews.php (إدارة التقييمات)         │  │
│  └───────────────────────────────────────────────┘  │
└──────────────────────┬──────────────────────────────┘
                       │ (SQL Queries)
                       ▼
┌─────────────────────────────────────────────────────┐
│              MySQL Database                         │
│  ┌───────────────────────────────────────────────┐  │
│  │  • users (المستخدمون)                         │  │
│  │  • products (المنتجات)                        │  │
│  │  • orders (الطلبات)                           │  │
│  │  • order_items (تفاصيل الطلبات)              │  │
│  │  • reviews (التقييمات والآراء)               │  │
│  │  • contact_messages (رسائل التواصل)          │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

---

## 2️⃣ تدفق البيانات

### التسجيل والدخول:
```
المستخدم يدخل البيانات
       ↓
script-new.php يرسل POST request
       ↓
api-users.php يتحقق من البيانات
       ↓
MySQL يحفظ/يتحقق من المستخدم
       ↓
يتم حفظ معلومات الجلسة (Session)
       ↓
يعود الرد JSON للمتصفح
```

### إضافة منتج للسلة:
```
العميل يضغط "أضف للسلة"
       ↓
JavaScript يضيف المنتج للمصفوفة cart[]
       ↓
يتم تحديث عداد السلة بالعدد
       ↓
يتم عرض تنبيه النجاح
```

### إنشاء طلب:
```
العميل يملأ البيانات ويضغط "أكمل"
       ↓
JavaScript يرسل بيانات السلة
       ↓
api-orders.php ينشئ سجل الطلب في MySQL
       ↓
يتم حفظ تفاصيل كل منتج في order_items
       ↓
يتم حساب الإجمالي والتوصيل
       ↓
يعود رقم الطلب الجديد
```

---

## 3️⃣ نقاط النهاية (API Endpoints)

### المنتجات
```
GET  /api-products.php              ← جميع المنتجات
GET  /api-products.php?category=food  ← منتجات الفئة
GET  /api-products.php?search=برجر   ← البحث
GET  /api-products.php?id=1         ← منتج محدد
POST /api-products.php              ← إضافة منتج (admin)
PUT  /api-products.php              ← تحديث منتج (admin)
DELETE /api-products.php            ← حذف منتج (admin)
```

### المستخدمون
```
POST /api-users.php?action=register      ← التسجيل
POST /api-users.php?action=login         ← الدخول
POST /api-users.php?action=logout        ← الخروج
GET  /api-users.php?action=current       ← المستخدم الحالي
POST /api-users.php?action=update        ← تحديث البيانات
POST /api-users.php?action=change_password ← تغيير كلمة المرور
```

### الطلبات
```
POST /api-orders.php?action=create       ← إنشاء طلب
GET  /api-orders.php?action=my_orders    ← طلبات المستخدم
GET  /api-orders.php?action=order_details&id=1 ← تفاصيل طلب
POST /api-orders.php?action=cancel&id=1  ← إلغاء طلب
GET  /api-orders.php?action=all_orders   ← جميع الطلبات (admin)
POST /api-orders.php?action=update_status ← تحديث الحالة (admin)
```

### التقييمات
```
POST /api-reviews.php?action=add_review  ← إضافة تقييم
GET  /api-reviews.php?action=product_reviews?product_id=1 ← تقييمات منتج
GET  /api-reviews.php?action=all_reviews ← جميع التقييمات
POST /api-reviews.php?action=send_message ← إرسال رسالة تواصل
GET  /api-reviews.php?action=all_messages ← جميع الرسائل (admin)
```

---

## 4️⃣ هيكل قاعدة البيانات

### جدول users (المستخدمون)
```sql
┌─────────────────────────────────────┐
│ users                               │
├─────────────────────────────────────┤
│ id (INT, PK, AUTO_INCREMENT)        │
│ name (VARCHAR 100)                  │
│ email (VARCHAR 100, UNIQUE)         │
│ password (VARCHAR 255) - مشفرة      │
│ phone (VARCHAR 20)                  │
│ address (VARCHAR 255)               │
│ is_admin (BOOLEAN) - افتراضي FALSE  │
│ created_at (TIMESTAMP)              │
│ updated_at (TIMESTAMP)              │
└─────────────────────────────────────┘
```

### جدول products (المنتجات)
```sql
┌──────────────────────────────────────┐
│ products                             │
├──────────────────────────────────────┤
│ id (INT, PK, AUTO_INCREMENT)         │
│ name (VARCHAR 150)                   │
│ description (TEXT)                   │
│ price (DECIMAL 10,2)                 │
│ category (ENUM: food/drinks/sweets)  │
│ image_url (VARCHAR 255)              │
│ is_available (BOOLEAN) - افتراضي T   │
│ created_at (TIMESTAMP)               │
│ updated_at (TIMESTAMP)               │
└──────────────────────────────────────┘
```

### جدول orders (الطلبات)
```sql
┌──────────────────────────────────────────┐
│ orders                                   │
├──────────────────────────────────────────┤
│ id (INT, PK, AUTO_INCREMENT)             │
│ user_id (INT, FK → users)                │
│ total_price (DECIMAL 10,2)               │
│ shipping_fee (DECIMAL 10,2) - افتراضي20  │
│ payment_method (ENUM: cash/card/wallet)  │
│ order_status (ENUM) ← 5 حالات            │
│ delivery_address (VARCHAR 255)           │
│ notes (TEXT)                             │
│ created_at (TIMESTAMP)                   │
│ updated_at (TIMESTAMP)                   │
└──────────────────────────────────────────┘
```

### جدول order_items (تفاصيل الطلبات)
```sql
┌────────────────────────────────────┐
│ order_items                        │
├────────────────────────────────────┤
│ id (INT, PK)                       │
│ order_id (INT, FK → orders)        │
│ product_id (INT, FK → products)    │
│ quantity (INT)                     │
│ price (DECIMAL 10,2)               │
│ created_at (TIMESTAMP)             │
└────────────────────────────────────┘
```

---

## 5️⃣ أمان النظام

### حماية كلمات المرور
```php
// عند التسجيل:
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// عند الدخول:
if (password_verify($enteredPassword, $storedHash)) {
    // كلمة المرور صحيحة
}
```

### منع حقن SQL (SQL Injection)
```php
// تنظيف البيانات:
$input = sanitize($_POST['name']);

// استخدام Prepared Statements (المثالي):
// سيتم تطبيقه في التطويرات المستقبلية
```

### التحكم بالوصول (Access Control)
```php
// فحص الجلسة:
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, "يجب تسجيل الدخول أولاً", null, 401);
}

// فحص الصلاحيات (Admin):
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    sendResponse(false, "ليس لديك صلاحية", null, 403);
}
```

### CORS (Cross-Origin Requests)
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

---

## 6️⃣ معالجة الأخطاء

### رموز الحالة (Status Codes)
```
200 ✅ OK - الطلب نجح
201 ✨ Created - تم إنشاء مورد جديد
400 ⚠️ Bad Request - البيانات غير صحيحة
401 🔐 Unauthorized - يجب تسجيل الدخول
403 🚫 Forbidden - ليس لديك صلاحية
404 ❌ Not Found - المورد غير موجود
500 💥 Server Error - خطأ في الخادم
```

### تنسيق الرد (Response Format)
```json
{
  "success": true,
  "message": "تم جلب البيانات بنجاح",
  "data": { /* البيانات المطلوبة */ }
}
```

---

## 7️⃣ الأداء والتحسينات

### قاعدة البيانات
- ✅ فهارس (Indexes) على الحقول المهمة
- ✅ استخدام `FOREIGN KEYS` للتكامل
- ✅ تخزين مؤقت (Caching) محلي

### الواجهة الأمامية
- ✅ تحميل صور من خوادم خارجية (CDN)
- ✅ تقليل حجم ملفات CSS و JS
- ✅ استخدام Lazy Loading للصور

### التطويرات المستقبلية
- [ ] استخدام Prepared Statements
- [ ] نظام ذاكرة تخزين مؤقتة (Redis)
- [ ] نقل الصور لخادم منفصل
- [ ] API Pagination للقوائم الطويلة
- [ ] Logging للأخطاء والعمليات

---

## 8️⃣ خطوات التطوير والاختبار

### الاختبار المحلي
```bash
# 1. تشغيل الخادم
# - ابدأ Apache و MySQL من XAMPP

# 2. اختبار المتصفح
# - http://localhost/My%20Order%20pro/

# 3. فحص قاعدة البيانات
# - phpMyAdmin: http://localhost/phpmyadmin
```

### أدوات التطوير
```
- Postman: لاختبار API endpoints
- VS Code: محرر الأكواد
- MySQL Workbench: إدارة قاعدة البيانات
- Chrome DevTools: تصحيح الأخطاء
```

---

## 9️⃣ قائمة المهام المستقبلية

### المرحلة القادمة:
- [ ] إضافة نظام دفع إلكترونية (Stripe, PayMob)
- [ ] إشعارات بريد إلكتروني تلقائية
- [ ] نظام تتبع الطلبات (GPS)
- [ ] تطبيق موبايل (React Native)
- [ ] لوحة تحليل البيانات (Dashboard)
- [ ] نظام المكافآت والنقاط
- [ ] دعم لغات متعددة (i18n)

---

## 🔟 ملاحظات مهمة

### الملفات الحساسة
```
⚠️ db-connect.php - لا تشاركها على GitHub
⚠️ firebase-config.js - تحتوي على مفاتيح سرية
⚠️ .env (مستقبلاً) - سيحتوي على بيانات حساسة
```

### التحديثات
```
✅ تحديث PHP بانتظام
✅ تحديث MySQL إلى الإصدارات الحديثة
✅ مراقبة مكتبات JavaScript الخارجية
```

### النسخ الاحتياطية
```
💾 احفظ نسخ من قاعدة البيانات كل يوم
💾 احفظ نسخ من الملفات المهمة
💾 استخدم Git للتحكم بالإصدارات
```

---

**آخر تحديث: 2026-01-31**
