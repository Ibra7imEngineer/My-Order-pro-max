-- ===================================
-- قاعدة بيانات My Order
-- ===================================

CREATE DATABASE IF NOT EXISTS my_order_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE my_order_db;

-- ===================================
-- جدول المستخدمين
-- ===================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول المنتجات
-- ===================================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category ENUM('food', 'drinks', 'sweets') NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول الطلبات
-- ===================================
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    shipping_fee DECIMAL(10, 2) DEFAULT 20,
    payment_method ENUM('cash', 'card', 'wallet') DEFAULT 'cash',
    order_status ENUM('جديد', 'قيد المعالجة', 'في الطريق', 'تم التسليم', 'ملغاة') DEFAULT 'جديد',
    delivery_address VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول تفاصيل الطلبات
-- ===================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول التقييمات والآراء
-- ===================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- جدول رسائل التواصل
-- ===================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- إدراج منتجات تجريبية
-- ===================================

-- أطعمة
INSERT INTO products (name, description, price, category, image_url) VALUES
('برجر كنج كلاسيك', 'برجر لذيذ بالدجاج والخضار الطازة', 120, 'food', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500'),
('بيتزا مارغريتا', 'بيتزا إيطالية تقليدية مع الجبن والطماطم', 150, 'food', 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=500'),
('سباجيتي بولونيز', 'معكرونة إيطالية لذيذة مع صلصة اللحم', 110, 'food', 'https://images.unsplash.com/photo-1516100882582-96c3a05fe590?w=500'),
('برجر دبل تشيز', 'برجر مزدوج مع جبن ولحم', 160, 'food', 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=500'),
('فاهيتا دجاج', 'دجاج مشوي مع بصل وفلفل حار', 130, 'food', 'https://i.ytimg.com/vi/9rQ9sCsuhRE/maxresdefault.jpg'),
('شاورما دجاج', 'شاورما دجاج لذيذة مع الخضار', 90, 'food', 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?w=500'),
('سلطة خضراء', 'سلطة طازة مع الخضراوات الصحية', 80, 'food', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500'),
('سوشي سيت', 'مجموعة متنوعة من السوشي الياباني', 220, 'food', 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=500');

-- مشروبات
INSERT INTO products (name, description, price, category, image_url) VALUES
('عصير برتقال طبيعي', 'عصير برتقال طبيعي 100% بدون إضافات', 30, 'drinks', 'https://images.unsplash.com/photo-1557800636-894a64c1696f?w=500'),
('عصير مانجو طازج', 'عصير مانجو فريش مع الثلج', 35, 'drinks', 'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?w=500'),
('لاتيه بارد', 'قهوة لاتيه باردة لذيذة', 40, 'drinks', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?w=500'),
('كابتشينو', 'قهوة كابتشينو مع الكريمة', 38, 'drinks', 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=500'),
('سموذي التوت', 'مشروب التوت المنعش', 48, 'drinks', 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=500');

-- حلويات
INSERT INTO products (name, description, price, category, image_url) VALUES
('كعكة الشوكولاتة', 'كعكة الشوكولاتة الفاخرة اللذيذة', 70, 'sweets', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500'),
('تشيز كيك فراولة', 'حلوى الجبن بنكهة الفراولة', 85, 'sweets', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500'),
('بسبوسة بالمكسرات', 'حلوى تقليدية بالمكسرات المختلفة', 45, 'sweets', 'https://www.exception-group.com/wp-content/uploads/2024/08/1.webp'),
('كريب نوتيلا', 'كريب بطبقات نوتيلا ولذيذة', 60, 'sweets', 'https://images.unsplash.com/photo-1519676867240-f03562e64548?w=500');

-- ===================================
-- إدراج مستخدم إدارة تجريبي
-- ===================================
INSERT INTO users (name, email, password, is_admin) VALUES
('مسؤول الموقع', 'admin@myorder.com', MD5('admin123'), TRUE);
