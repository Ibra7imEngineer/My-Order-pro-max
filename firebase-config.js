// Import Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js";
import { getDatabase, ref, push, set, onValue, remove, update } from "https://www.gstatic.com/firebasejs/10.7.0/firebase-database.js";
import { getAuth, signInWithEmailAndPassword, createUserWithEmailAndPassword, signOut, onAuthStateChanged, GoogleAuthProvider, signInWithPopup, signInWithRedirect, getRedirectResult } from "https://www.gstatic.com/firebasejs/10.7.0/firebase-auth.js";

// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyDq-yBidQtUVlraNl7ev49iDJ6EtpZ8raQ",
  authDomain: "my-order-c6010.firebaseapp.com",
  databaseURL: "https://my-order-c6010-default-rtdb.firebaseio.com",
  projectId: "my-order-c6010",
  storageBucket: "my-order-c6010.firebasestorage.app",
  messagingSenderId: "601099668106",
  appId: "1:601099668106:web:bc59d6d77ae1127f137b73",
  measurementId: "G-X1J7Z5WD14"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const database = getDatabase(app);
const auth = getAuth(app);

console.log('✅ Firebase initialized successfully');

// =====================================================
// دوال إدارة الطلبات في Firebase
// =====================================================

// حفظ طلب جديد
function saveOrderToFirebase(orderData) {
    const ordersRef = ref(database, 'orders');
    const newOrderRef = push(ordersRef);
    const orderWithTimestamp = {
        ...orderData,
        orderId: newOrderRef.key,
        timestamp: new Date().toISOString(),
        status: 'جديد'
    };
    set(newOrderRef, orderWithTimestamp)
        .then(() => {
            console.log('✅ تم حفظ الطلب بنجاح:', orderWithTimestamp);
            showNotification('✅ تم حفظ الطلب في النظام', 'success');
        })
        .catch(error => {
            console.error('❌ خطأ في حفظ الطلب:', error);
        });
}

// جلب جميع الطلبات
function fetchAllOrders(callback) {
    const ordersRef = ref(database, 'orders');
    onValue(ordersRef, (snapshot) => {
        const data = snapshot.val();
        const orders = data ? Object.values(data) : [];
        callback(orders);
    });
}

// حفظ المنتجات في Firebase
function syncMenuToFirebase() {
    const menuRef = ref(database, 'menu');
    set(menuRef, menuItems)
        .then(() => {
            console.log('✅ تم مزامنة القائمة مع Firebase');
        })
        .catch(error => {
            console.error('❌ خطأ في مزامنة القائمة:', error);
        });
}

// تحميل المنتجات من Firebase
function loadMenuFromFirebase(callback) {
    const menuRef = ref(database, 'menu');
    onValue(menuRef, (snapshot) => {
        const data = snapshot.val();
        if (data) {
            menuItems = Array.isArray(data) ? data : Object.values(data);
            callback(menuItems);
        }
    });
}

// حفظ التقييمات في Firebase
function saveReviewToFirebase(review) {
    const reviewsRef = ref(database, 'reviews');
    const newReviewRef = push(reviewsRef);
    const reviewWithTimestamp = {
        ...review,
        timestamp: new Date().toISOString()
    };
    set(newReviewRef, reviewWithTimestamp)
        .then(() => {
            console.log('✅ تم حفظ التقييم بنجاح');
            showNotification('✅ شكراً على تقييمك!', 'success');
        })
        .catch(error => {
            console.error('❌ خطأ في حفظ التقييم:', error);
        });
}

// جلب التقييمات من Firebase
function fetchReviewsFromFirebase(callback) {
    const reviewsRef = ref(database, 'reviews');
    onValue(reviewsRef, (snapshot) => {
        const data = snapshot.val();
        const allReviews = data ? Object.values(data) : [];
        callback(allReviews);
    });
}

// حذف طلب من Firebase
function deleteOrderFromFirebase(orderId) {
    const orderRef = ref(database, `orders/${orderId}`);
    remove(orderRef)
        .then(() => {
            console.log('✅ تم حذف الطلب بنجاح');
            showNotification('✅ تم حذف الطلب', 'success');
        })
        .catch(error => {
            console.error('❌ خطأ في حذف الطلب:', error);
        });
}

// تحديث حالة الطلب
function updateOrderStatus(orderId, newStatus) {
    const orderRef = ref(database, `orders/${orderId}`);
    update(orderRef, { status: newStatus })
        .then(() => {
            console.log('✅ تم تحديث حالة الطلب:', newStatus);
            showNotification(`✅ تم تحديث الحالة إلى: ${newStatus}`, 'success');
        })
        .catch(error => {
            console.error('❌ خطأ في تحديث الحالة:', error);
        });
}

// =====================================================
// دوال المصادقة (تسجيل الدخول)
// =====================================================

// تسجيل دخول مسؤول Firebase
function firebaseAdminLogin(email, password) {
    signInWithEmailAndPassword(auth, email, password)
        .then((userCredential) => {
            console.log('✅ تسجيل دخول ناجح:', userCredential.user.email);
            sessionStorage.setItem('isAdmin', 'true');
            sessionStorage.setItem('adminEmail', userCredential.user.email);
            showNotification('✅ تم تسجيل الدخول بنجاح', 'success');
            showPage('admin-page');
        })
        .catch((error) => {
            console.error('❌ خطأ في تسجيل الدخول:', error.message);
            showNotification('❌ بيانات الدخول غير صحيحة', 'error');
        });
}

// تسجيل خروج
function firebaseLogout() {
    signOut(auth)
        .then(() => {
            console.log('✅ تم تسجيل الخروج');
            sessionStorage.removeItem('isAdmin');
            sessionStorage.removeItem('adminEmail');
            sessionStorage.removeItem('user');
            showNotification('✅ تم تسجيل الخروج بنجاح', 'success');
            showPage('home-page');
        })
        .catch((error) => {
            console.error('❌ خطأ في تسجيل الخروج:', error);
        });
}

// مراقبة حالة المصادقة
onAuthStateChanged(auth, (user) => {
    if (user) {
        console.log('✅ المستخدم مسجل دخول:', user.email);
        // خزّن بيانات المستخدم العامة لاستخدامها في الواجهة
        const u = { email: user.email, name: user.displayName || '', photo: user.photoURL || '' };
        sessionStorage.setItem('user', JSON.stringify(u));
    } else {
        console.log('❌ المستخدم غير مسجل دخول');
        sessionStorage.removeItem('user');
    }
});

// تسجيل دخول عبر Google للمستخدمين العاديين
function googleSignIn() {
    const provider = new GoogleAuthProvider();
    // حاول فتح نافذة منبثقة أولاً، وإذا فشل (محجوبة) استخدم إعادة التوجيه
    signInWithPopup(auth, provider)
        .then((result) => {
            const user = result.user;
            const u = { email: user.email, name: user.displayName || '', photo: user.photoURL || '' };
            sessionStorage.setItem('user', JSON.stringify(u));
            console.log('✅ Google sign-in success (popup):', user.email);
            showNotification('✅ تم تسجيل الدخول عبر Google', 'success');
            showPage('home-page');
        })
        .catch((error) => {
            console.warn('⚠️ Google sign-in popup failed:', error.code, error.message);
            // إذا كانت دوال التكوين غير موجودة في Firebase (لم يتم تفعيل Google provider)
            if (error.code === 'auth/configuration-not-found') {
                console.error('❌ Google provider not configured in Firebase Auth:', error);
                showNotification('❌ تسجيل الدخول عبر Google غير مفعّل في إعدادات Firebase. اضغط الزر لفتح صفحة إعدادات المشروع في Firebase Console.', 'warning');
                // علم أن المصادقة عبر Google غير متاحة برمجياً
                window.firebaseDB = window.firebaseDB || {};
                window.firebaseDB.googleAuthAvailable = false;

                // لا نخفي الزر؛ بدلًا من ذلك نُعيد تعريف الدالة لفتح صفحة إعدادات Firebase للمشروع
                const consoleUrl = `https://console.firebase.google.com/project/${firebaseConfig.projectId}/authentication/providers`;
                window.googleSignIn = function() {
                    showNotification('⚠️ فتح إعدادات Firebase في علامة جديدة. فعّل Google Sign-in ثم أعد المحاولة.', 'info');
                    try { window.open(consoleUrl, '_blank'); } catch(e) { console.warn('Unable to open Firebase Console:', e); }
                };

                // ضع تلميحاً مرئياً على الزر إن أمكن
                try {
                    const btn = document.querySelector('.google-sign');
                    if (btn) {
                        btn.setAttribute('title', 'Google Sign-in غير مفعّل — اضغط لفتح Firebase Console');
                        btn.style.opacity = '0.95';
                    }
                } catch (e) { /* silent */ }

                return;
            }

            // إذا كانت المشكلة بسبب حظر النوافذ المنبثقة أو سياسات المتصفح
            if (error.code === 'auth/popup-blocked' || error.code === 'auth/popup-closed-by-user' || error.code === 'auth/web-storage-unsupported' || error.code === 'auth/network-request-failed') {
                showNotification('⚠️ نافذة تسجيل الدخول محجوبة؛ سيتم تحويلك لصفحة تسجيل Google', 'warning');
                // افتح تسجيل الدخول باستخدام إعادة التوجيه كبديل
                signInWithRedirect(auth, provider);
                return;
            }

            // عرض تفاصيل الخطأ في الكونسول وللمستخدم
            console.error('❌ Google sign-in error details:', error);
            showNotification(`❌ فشل تسجيل الدخول عبر Google: ${error.message}`, 'error');
        });
}

// معالجة نتيجة إعادة التوجيه عند تحميل الصفحة (إذا تم استخدام signInWithRedirect)
getRedirectResult(auth)
    .then((result) => {
        if (result && result.user) {
            const user = result.user;
            const u = { email: user.email, name: user.displayName || '', photo: user.photoURL || '' };
            sessionStorage.setItem('user', JSON.stringify(u));
            console.log('✅ Google sign-in success (redirect):', user.email);
            showNotification('✅ تم تسجيل الدخول عبر Google', 'success');
            showPage('home-page');
        }
    })
    .catch((error) => {
        // في حال لم تكن هناك نتيجة إعادة توجيه أو فشل، سجّل فقط
        if (error) {
            console.warn('⚠️ getRedirectResult error:', error.code, error.message);
        }
    });

// =====================================================
// دوال مزامنة البيانات
// =====================================================

// مزامنة شاملة عند بدء التطبيق
function initializeFirebaseSync() {
    console.log('🔄 جاري مزامنة البيانات مع Firebase...');
    
    // تحميل المنتجات
    loadMenuFromFirebase((items) => {
        menuItems = items;
        renderMenu(menuItems);
        console.log('✅ تم تحميل المنتجات من Firebase');
    });
    
    // تحميل التقييمات
    fetchReviewsFromFirebase((reviewList) => {
        reviews = reviewList;
        renderReviews();
        console.log('✅ تم تحميل التقييمات من Firebase');
    });
}

// تصدير الدوال للاستخدام العام
window.firebaseDB = {
    saveOrderToFirebase,
    fetchAllOrders,
    syncMenuToFirebase,
    loadMenuFromFirebase,
    saveReviewToFirebase,
    fetchReviewsFromFirebase,
    deleteOrderFromFirebase,
    updateOrderStatus,
    firebaseAdminLogin,
    firebaseLogout,
    initializeFirebaseSync
};

// إضافة دوال المصادقة للنافذة
window.firebaseDB.googleSignIn = googleSignIn;
// تعرّض دالة googleSignIn كدالة عالمية لتوافق استدعاءات inline في index.html
window.googleSignIn = googleSignIn;

// ==========================
// دوال تهيئة (Seeding) للبيانات
// ==========================
function seedMenuData(menuArray) {
    if (!Array.isArray(menuArray)) return Promise.reject(new Error('menuArray must be an array'));
    const menuRef = ref(database, 'menu');
    return set(menuRef, menuArray)
        .then(() => {
            console.log('✅ تم تهيئة قائمة الطعام في Firebase');
            return true;
        })
        .catch(err => {
            console.error('❌ خطأ أثناء تهيئة القائمة:', err);
            throw err;
        });
}

function seedBranches(branchesArray) {
    if (!Array.isArray(branchesArray)) return Promise.reject(new Error('branchesArray must be an array'));
    const refBranches = ref(database, 'branches');
    return set(refBranches, branchesArray)
        .then(() => {
            console.log('✅ تم تهيئة الفروع في Firebase');
            return true;
        })
        .catch(err => {
            console.error('❌ خطأ أثناء تهيئة الفروع:', err);
            throw err;
        });
}

function seedAdminUser(adminObj) {
    if (typeof adminObj !== 'object') return Promise.reject(new Error('adminObj must be an object'));
    const adminRef = ref(database, 'admin');
    return set(adminRef, adminObj)
        .then(() => {
            console.log('✅ تم تهيئة بيانات المشرف في Firebase');
            return true;
        })
        .catch(err => {
            console.error('❌ خطأ أثناء تهيئة بيانات المشرف:', err);
            throw err;
        });
}

// أضف الدوال لمجموعة التصدير في النافذة
window.firebaseDB.seedMenuData = seedMenuData;
window.firebaseDB.seedBranches = seedBranches;
window.firebaseDB.seedAdminUser = seedAdminUser;

console.log('✅ Firebase functions loaded and ready to use');
