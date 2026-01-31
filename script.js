/* ===================================================
   My Order - نظام طلب الطعام الاحترافي
   ===================================================
   API Version: PHP + MySQL
*/

// ====== إعدادات وثوابت ======
const API_BASE = '';  // اذا كان الموقع في نفس المجلد
let cart = [];
let menuItems = [];
let currentUser = null;
const SHIPPING_FEE = 20;

// ====== دوال الـ API ======

// 1️⃣ دوال المنتجات
async function fetchProducts(category = null) {
    try {
        let url = 'api-products.php';
        if (category) url += `?category=${category}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            menuItems = data.data;
            return menuItems;
        } else {
            showNotification('خطأ في جلب المنتجات: ' + data.message, 'error');
            return [];
        }
    } catch (error) {
        console.error('خطأ في جلب المنتجات:', error);
        showNotification('خطأ في الاتصال بالخادم', 'error');
        return [];
    }
}

async function searchProducts(searchTerm) {
    try {
        const response = await fetch(`api-products.php?search=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        }
        return [];
    } catch (error) {
        console.error('خطأ في البحث:', error);
        return [];
    }
}

// 2️⃣ دوال المستخدمين
async function registerUser(name, email, password, phone = '', address = '') {
    try {
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('phone', phone);
        formData.append('address', address);
        
        const response = await fetch('api-users.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.data;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            showNotification('✅ تم التسجيل بنجاح', 'success');
            return data.data;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return null;
        }
    } catch (error) {
        console.error('خطأ في التسجيل:', error);
        showNotification('خطأ في الاتصال بالخادم', 'error');
        return null;
    }
}

async function loginUser(email, password) {
    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('email', email);
        formData.append('password', password);
        
        const response = await fetch('api-users.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.data;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            showNotification('✅ تم تسجيل الدخول بنجاح', 'success');
            return data.data;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return null;
        }
    } catch (error) {
        console.error('خطأ في تسجيل الدخول:', error);
        showNotification('خطأ في الاتصال بالخادم', 'error');
        return null;
    }
}

async function logoutUser() {
    try {
        const formData = new FormData();
        formData.append('action', 'logout');
        
        await fetch('api-users.php', {
            method: 'POST',
            body: formData
        });
        
        currentUser = null;
        localStorage.removeItem('currentUser');
        showNotification('✅ تم تسجيل الخروج بنجاح', 'success');
    } catch (error) {
        console.error('خطأ في تسجيل الخروج:', error);
    }
}

async function getCurrentUser() {
    try {
        const response = await fetch('api-users.php?action=current');
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.data;
            return currentUser;
        }
        return null;
    } catch (error) {
        console.error('خطأ في جلب بيانات المستخدم:', error);
        return null;
    }
}

// 3️⃣ دوال الطلبات
async function createOrder(items, paymentMethod, deliveryAddress, notes = '') {
    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('items', JSON.stringify(items));
        formData.append('payment_method', paymentMethod);
        formData.append('delivery_address', deliveryAddress);
        formData.append('notes', notes);
        
        const response = await fetch('api-orders.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ تم إنشاء الطلب بنجاح', 'success');
            return data.data;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return null;
        }
    } catch (error) {
        console.error('خطأ في إنشاء الطلب:', error);
        showNotification('خطأ في الاتصال بالخادم', 'error');
        return null;
    }
}

async function getUserOrders() {
    try {
        const response = await fetch('api-orders.php?action=my_orders');
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return [];
        }
    } catch (error) {
        console.error('خطأ في جلب الطلبات:', error);
        return [];
    }
}

async function getOrderDetails(orderId) {
    try {
        const response = await fetch(`api-orders.php?action=order_details&id=${orderId}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        }
        return null;
    } catch (error) {
        console.error('خطأ في جلب تفاصيل الطلب:', error);
        return null;
    }
}

async function cancelOrder(orderId) {
    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('id', orderId);
        
        const response = await fetch('api-orders.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ تم إلغاء الطلب بنجاح', 'success');
            return true;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('خطأ في إلغاء الطلب:', error);
        return false;
    }
}

// 4️⃣ دوال التقييمات
async function submitReview(productId, rating, comment = '') {
    try {
        const formData = new FormData();
        formData.append('action', 'add_review');
        formData.append('product_id', productId);
        formData.append('rating', rating);
        formData.append('comment', comment);
        
        const response = await fetch('api-reviews.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ شكراً لتقييمك', 'success');
            return true;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('خطأ في إضافة التقييم:', error);
        return false;
    }
}

async function getProductReviews(productId) {
    try {
        const response = await fetch(`api-reviews.php?action=product_reviews&product_id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        }
        return { reviews: [], average_rating: 0, total_reviews: 0 };
    } catch (error) {
        console.error('خطأ في جلب التقييمات:', error);
        return { reviews: [], average_rating: 0, total_reviews: 0 };
    }
}

async function sendContactMessage(name, email, subject, message) {
    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('subject', subject);
        formData.append('message', message);
        
        const response = await fetch('api-reviews.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ تم إرسال رسالتك بنجاح', 'success');
            return true;
        } else {
            showNotification('❌ ' + data.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('خطأ في إرسال الرسالة:', error);
        return false;
    }
}

// ====== دوال إدارة السلة ======

function addToCart(productId) {
    const product = menuItems.find(p => p.id === productId);
    if (!product) {
        showNotification('❌ المنتج غير موجود', 'error');
        return;
    }
    
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image_url: product.image_url,
            quantity: 1
        });
    }
    
    updateCartCount();
    renderCartItems();
    showNotification(`✅ تمت إضافة ${product.name} للسلة`, 'success');
}

function removeFromCart(index) {
    const item = cart[index];
    cart.splice(index, 1);
    updateCartCount();
    renderCartItems();
    showNotification(`✅ تم إزالة ${item.name} من السلة`, 'success');
}

function updateCartCount() {
    const countElement = document.getElementById('cart-count');
    if (countElement) {
        countElement.textContent = cart.length;
    }
}

function increaseQuantity(index) {
    if (cart[index]) {
        cart[index].quantity += 1;
        renderCartItems();
    }
}

function decreaseQuantity(index) {
    if (cart[index]) {
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
        } else {
            removeFromCart(index);
            return;
        }
        renderCartItems();
    }
}

function calculateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    return {
        subtotal: subtotal,
        shipping: SHIPPING_FEE,
        total: subtotal + SHIPPING_FEE
    };
}

// ====== دوال العرض والتصيير ======

function renderMenu(products = menuItems) {
    const grid = document.getElementById('itemsGrid');
    if (!grid) return;
    
    grid.innerHTML = products.map(product => `
        <div class="res-card">
            <div class="card-img-container">
                <img src="${product.image_url || 'https://via.placeholder.com/280x200'}" alt="${product.name}">
            </div>
            <div class="res-info" style="padding:15px; text-align:center;">
                <h4 style="font-size:18px; margin-bottom:8px;">${product.name}</h4>
                ${product.description ? `<p style="color:#666; font-size:12px; margin-bottom:8px;">${product.description.substring(0, 50)}...</p>` : ''}
                <p style="color:var(--primary); font-weight:bold; font-size:17px; margin-bottom:12px;">${product.price} ج.م</p>
                <button class="add-btn-card" onclick="addToCart(${product.id})"
                        style="width:100%; padding:12px; font-size:16px; background:var(--primary); color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer; transition: 0.3s;">
                    أضف للسلة <i class="fa fa-plus-circle"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function renderCartItems() {
    const cartItemsList = document.getElementById('cartItemsList');
    const orderSummary = document.getElementById('orderSummary');
    const totalPrice = document.getElementById('totalPrice');
    const subtotalPrice = document.getElementById('subtotalPrice');

    if (!cartItemsList) return;

    if (cart.length === 0) {
        cartItemsList.innerHTML = `
            <div style="text-align:center; padding:60px 20px;">
                <i class="fa fa-shopping-cart" style="font-size:64px; color:#BDC3C7; margin-bottom:20px;"></i>
                <h3 style="color:#7F8C8D; margin-bottom:10px;">السلة فارغة</h3>
                <p style="color:#BDC3C7; margin-bottom:30px;">لم تضف أي منتجات بعد</p>
                <button onclick="showPage('menu-page')" style="background:linear-gradient(135deg, #FF6B35, #FF8E5F); color:white; border:none; padding:12px 30px; border-radius:8px; cursor:pointer; font-weight:600;">
                    <i class="fa fa-arrow-right"></i> اذهب للقائمة
                </button>
            </div>
        `;
        if(orderSummary) orderSummary.innerHTML = '';
        if(totalPrice) totalPrice.textContent = '0';
        return;
    }

    let html = '';
    let summaryHtml = '';

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        
        html += `
            <div class="cart-item" style="background:white; border:1px solid #E8EAED; border-radius:12px; padding:20px; margin-bottom:15px; display:flex; gap:15px; align-items:center; transition:all 0.3s;">
                <div style="flex-shrink:0;">
                    <img src="${item.image_url || 'https://via.placeholder.com/100'}" alt="${item.name}" style="width:100px; height:100px; object-fit:cover; border-radius:8px;">
                </div>
                <div style="flex-grow:1;">
                    <h4 style="margin:0 0 8px 0; color:#2C3E50; font-size:16px; font-weight:700;">${item.name}</h4>
                    <div style="display:flex; align-items:center; gap:8px; margin-top:10px;">
                        <button onclick="decreaseQuantity(${index})" style="width:32px; height:32px; background:#F0F0F0; border:1px solid #DDD; border-radius:6px; cursor:pointer; font-weight:600; transition:0.2s;">−</button>
                        <span style="width:40px; text-align:center; font-weight:700; color:#FF6B35;">${item.quantity}</span>
                        <button onclick="increaseQuantity(${index})" style="width:32px; height:32px; background:#F0F0F0; border:1px solid #DDD; border-radius:6px; cursor:pointer; font-weight:600; transition:0.2s;">+</button>
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <p style="margin:0 0 8px 0; color:#FF6B35; font-size:16px; font-weight:700;">${itemTotal} ج.م</p>
                    <p style="margin:0; color:#7F8C8D; font-size:12px;">${item.price} ج.م × ${item.quantity}</p>
                    <button onclick="removeFromCart(${index})" style="margin-top:10px; background:#FFE5DC; color:#FF6B35; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:all 0.3s;">
                        <i class="fa fa-trash"></i> حذف
                    </button>
                </div>
            </div>
        `;

        summaryHtml += `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.2); font-size:14px;">
                <span>${item.name} × ${item.quantity}</span>
                <strong>${itemTotal} ج.م</strong>
            </div>
        `;
    });

    cartItemsList.innerHTML = html;
    if(orderSummary) orderSummary.innerHTML = summaryHtml;

    const totals = calculateCartTotal();
    if(subtotalPrice) subtotalPrice.textContent = totals.subtotal;
    if(totalPrice) totalPrice.textContent = totals.total;
}

function searchFunction() {
    const term = document.getElementById('mainSearch').value.toLowerCase();
    const filtered = menuItems.filter(item => item.name.toLowerCase().includes(term));
    renderMenu(filtered);
}

function filterItems(category) {
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(btn => btn.classList.remove('active'));
    
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    if (category === 'all') {
        renderMenu(menuItems);
    } else {
        const filtered = menuItems.filter(item => item.category === category);
        renderMenu(filtered);
    }
}

// ====== نظام التنقل والصفحات ======

function showPage(pageId) {
    // حماية لوحة الإدارة
    if (pageId === 'admin' || pageId === 'admin-page') {
        if (!currentUser || !currentUser.is_admin) {
            showNotification('⛔ ليس لديك صلاحية الدخول لوحة الإدارة', 'error');
            showPage('login-page');
            return;
        }
    }
    
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => {
        page.classList.remove('active');
        page.style.display = 'none';
    });
    
    let targetId = pageId;
    if (!pageId.endsWith('-page')) {
        targetId = pageId + '-page';
    }
    
    const activePage = document.getElementById(targetId);
    if (activePage) {
        activePage.classList.add('active');
        activePage.style.display = 'block';
    }
    
    // تحديث المحتوى
    if (pageId === 'menu' || pageId === 'menu-page') {
        renderMenu(menuItems);
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ====== إشعارات ======

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${
            type === 'success' ? 'linear-gradient(135deg, #4CAF50, #66BB6A)' :
            type === 'error' ? 'linear-gradient(135deg, #FF5252, #FF6E40)' :
            type === 'warning' ? 'linear-gradient(135deg, #FFC107, #FFD54F)' :
            'linear-gradient(135deg, #2196F3, #42A5F5)'
        };
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        font-weight: 600;
        font-size: 14px;
        max-width: 350px;
        word-wrap: break-word;
    `;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// ====== تهيئة الموقع ======

window.addEventListener('load', async () => {
    // جلب المنتجات
    await fetchProducts();
    
    // التحقق من المستخدم
    await getCurrentUser();
    
    // عرض الصفحة الرئيسية
    showPage('home-page');
    
    // إضافة الأنميشن
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes slideIn { 
            from { transform: translateX(400px); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
        }
        @keyframes fadeOut { 
            to { opacity: 0; } 
        }
    `;
    document.head.appendChild(style);
});

