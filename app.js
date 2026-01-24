// ============================
// ИНИЦИАЛИЗАЦИЯ И КОНСТАНТЫ
// ============================
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();
tg.enableClosingConfirmation();

// Конфигурация API (Vercel Serverless Functions)
const API_BASE_URL = 'https://ваш-проект.vercel.app/api';

// Пока используем демо-данные, пока не настроим Vercel
const USE_DEMO_DATA = true;

const ADMIN_ID = 'ВАШ_TELEGRAM_ID'; // Замените на ваш ID

let products = [];
let categories = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];
const user = tg.initDataUnsafe.user;

// ============================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================

// Функция для получения картинок товаров
function getProductImage(productName) {
    const color = '0088cc';
    const textColor = 'ffffff';
    const encodedName = encodeURIComponent(productName);
    return `https://via.placeholder.com/600x400/${color}/${textColor}?text=${encodedName}`;
}

// Уведомления
function showNotification(message, duration = 3000) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, duration);
}

// ============================
// ЗАГРУЗКА ПРИЛОЖЕНИЯ
// ============================
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Проверка режима администратора
        if (user && user.id.toString() === ADMIN_ID) {
            document.getElementById('adminPanel').style.display = 'block';
            showNotification('👑 Режим администратора активирован');
        }
        
        // Загрузка данных
        if (USE_DEMO_DATA) {
            await loadDemoData();
        } else {
            await Promise.all([
                loadCategoriesFromAPI(),
                loadProductsFromAPI()
            ]);
        }
        
        updateCartUI();
        
        if (user) {
            showNotification(`Добро пожаловать, ${user.first_name}! 👋`, 2000);
        }
        
        // Настройка кнопки "Назад" в Telegram
        tg.BackButton.show();
        tg.BackButton.onClick(() => {
            if (document.getElementById('cartModal').classList.contains('active')) {
                closeCart();
            } else {
                if (confirm('Закрутить магазин?')) {
                    tg.close();
                }
            }
        });
        
    } catch (error) {
        console.error('Ошибка загрузки:', error);
        showNotification('⚠️ Ошибка загрузки каталога', 5000);
        await loadDemoData();
    }
});

// ============================
// API ФУНКЦИИ (для работы с Vercel)
// ============================

async function loadCategoriesFromAPI() {
    try {
        const response = await fetch(`${API_BASE_URL}/?action=get_categories`);
        const data = await response.json();
        
        if (data.success) {
            categories = data.categories;
            renderCategories();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
        throw error;
    }
}

async function loadProductsFromAPI(categoryId = 'all') {
    try {
        document.getElementById('loading').style.display = 'flex';
        
        let url = `${API_BASE_URL}/?action=get_products`;
        if (categoryId !== 'all') {
            url += `&category_id=${categoryId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            products = data.products;
            displayProducts(products);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
        throw error;
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

// ============================
// ДЕМО-ДАННЫЕ (пока не настроен Vercel)
// ============================

async function loadDemoData() {
    // Демо-категории
    categories = [
        { id: 1, name: '📱 Электроника', slug: 'electronics' },
        { id: 2, name: '👕 Одежда', slug: 'clothing' },
        { id: 3, name: '👟 Обувь', slug: 'shoes' },
        { id: 4, name: '⌚ Аксессуары', slug: 'accessories' },
        { id: 5, name: '📚 Книги', slug: 'books' },
        { id: 6, name: '🏠 Для дома', slug: 'home' }
    ];
    
    // Демо-товары
    products = [
        {
            id: 1,
            name: "iPhone 15 Pro",
            price: 89990,
            image: getProductImage("iPhone 15 Pro"),
            category_id: 1,
            category_name: "📱 Электроника",
            description: "Новый iPhone с камерой 48MP и динамическим островом",
            stock: 15,
            rating: 4.8,
            old_price: 99990,
            discount: 10
        },
        {
            id: 2,
            name: "MacBook Air M2",
            price: 129990,
            image: getProductImage("MacBook Air M2"),
            category_id: 1,
            category_name: "📱 Электроника",
            description: "Ультратонкий ноутбук Apple с чипом M2",
            stock: 8,
            rating: 4.9
        },
        {
            id: 3,
            name: "Nike Air Max 270",
            price: 12990,
            image: getProductImage("Nike Air Max"),
            category_id: 3,
            category_name: "👟 Обувь",
            description: "Легендарные кроссовки с технологией Air",
            stock: 20,
            rating: 4.8,
            old_price: 14990,
            discount: 13
        },
        {
            id: 4,
            name: "Футболка Premium",
            price: 2990,
            image: getProductImage("Футболка Premium"),
            category_id: 2,
            category_name: "👕 Одежда",
            description: "100% премиальный хлопок, идеальный крой",
            stock: 50,
            rating: 4.5,
            old_price: 3990,
            discount: 25
        }
    ];
    
    renderCategories();
    displayProducts(products);
    document.getElementById('loading').style.display = 'none';
}

// ============================
// ОТОБРАЖЕНИЕ КАТЕГОРИЙ
// ============================

function renderCategories() {
    const container = document.getElementById('categories');
    // Очищаем все кроме первой кнопки
    while (container.children.length > 1) {
        container.removeChild(container.lastChild);
    }
    
    categories.forEach(category => {
        const btn = document.createElement('button');
        btn.className = 'category-btn';
        btn.textContent = category.name;
        btn.onclick = () => filterProducts(category.id);
        container.appendChild(btn);
    });
}

// ============================
// ОТОБРАЖЕНИЕ ТОВАРОВ
// ============================

function displayProducts(productsToShow) {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';
    
    if (productsToShow.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--gray-600);">
                <div style="font-size: 48px; margin-bottom: 16px;">😕</div>
                <h3 style="margin-bottom: 8px;">Товары не найдены</h3>
                <p>Попробуйте выбрать другую категорию</p>
            </div>
        `;
        return;
    }
    
    productsToShow.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        
        const hasDiscount = product.old_price && product.discount;
        const discountBadge = hasDiscount ? `
            <div style="position: absolute; top: 12px; right: 12px; background: var(--red); color: white; padding: 4px 8px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                -${product.discount}%
            </div>
        ` : '';
        
        const priceHTML = hasDiscount ? `
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <div class="product-price">${product.price.toLocaleString()} ₽</div>
                <div style="font-size: 14px; color: var(--gray-500); text-decoration: line-through;">
                    ${product.old_price.toLocaleString()} ₽
                </div>
            </div>
        ` : `<div class="product-price">${product.price.toLocaleString()} ₽</div>`;
        
        card.innerHTML = `
            <div class="product-image-container">
                <img src="${product.image}" 
                     alt="${product.name}" 
                     class="product-image"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400/0088cc/ffffff?text=Товар'">
                <div class="stock-badge">
                    ${product.stock} в наличии
                </div>
                ${discountBadge}
            </div>
            <div class="product-content">
                <div class="product-category">${product.category_name || '📦 Товар'}</div>
                <h3 class="product-title">${product.name}</h3>
                <p class="product-description">${product.description}</p>
                <div class="product-footer">
                    ${priceHTML}
                    <button class="add-to-cart" 
                            onclick="addToCart(${product.id})"
                            ${product.stock === 0 ? 'disabled' : ''}
                            title="${product.stock > 0 ? 'Добавить в корзину' : 'Нет в наличии'}">
                        ${product.stock > 0 ? '🛒 В корзину' : '😔 Нет в наличии'}
                    </button>
                </div>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

// ============================
// ФИЛЬТРАЦИЯ
// ============================

function filterProducts(categoryId) {
    // Обновляем активные кнопки
    document.querySelectorAll('.category-btn').forEach((btn, index) => {
        if (index === 0 && categoryId === 'all') {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    event.target.classList.add('active');
    
    if (categoryId === 'all') {
        displayProducts(products);
    } else {
        const filtered = products.filter(p => p.category_id === categoryId);
        displayProducts(filtered);
    }
}

// ============================
// КОРЗИНА (те же функции)
// ============================

function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product || product.stock === 0) return;
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        if (existingItem.quantity >= product.stock) {
            showNotification('⚠️ Достигнут лимит товара на складе');
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }
    
    saveCart();
    updateCartUI();
    showNotification(`✅ "${product.name}" добавлен в корзину`);
    if (tg && tg.HapticFeedback) {
        tg.HapticFeedback.impactOccurred('light');
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    updateCartUI();
    showNotification('🗑️ Товар удален из корзины');
    if (tg && tg.HapticFeedback) {
        tg.HapticFeedback.impactOccurred('light');
    }
}

function updateQuantity(productId, delta) {
    const item = cart.find(item => item.id === productId);
    if (!item) return;
    
    const product = products.find(p => p.id === productId);
    const newQuantity = item.quantity + delta;
    
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    if (newQuantity > product.stock) {
        showNotification(`⚠️ Максимум ${product.stock} шт. на складе`);
        return;
    }
    
    item.quantity = newQuantity;
    saveCart();
    updateCartUI();
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartUI() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    document.getElementById('cartCount').textContent = totalItems;
    document.getElementById('floatingCartCount').textContent = totalItems;
    
    const cartItemsContainer = document.getElementById('cartItems');
    const cartTotalElement = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h3>Корзина пуста</h3>
                <p style="margin-top: 8px; color: var(--gray-600);">
                    Добавьте товары из каталога
                </p>
                <button onclick="closeCart(); filterProducts('all');" 
                        style="margin-top: 20px; padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    🛍️ Перейти к покупкам
                </button>
            </div>
        `;
        cartTotalElement.textContent = '0 ₽';
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '💳 Оформить заказ';
    } else {
        let itemsHTML = '';
        let total = 0;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            itemsHTML += `
                <div class="cart-item">
                    <img src="${item.image}" 
                         alt="${item.name}" 
                         class="cart-item-image"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/200x200/0088cc/ffffff?text=Товар'">
                    <div class="cart-item-details">
                        <div class="cart-item-title">${item.name}</div>
                        <div class="cart-item-price">${item.price.toLocaleString()} ₽</div>
                        <div class="cart-item-actions">
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <span class="quantity-value">${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                            <button class="remove-btn" onclick="removeFromCart(${item.id})">
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItemsContainer.innerHTML = itemsHTML;
        cartTotalElement.textContent = `${total.toLocaleString()} ₽`;
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = `💳 Оформить заказ • ${total.toLocaleString()} ₽`;
    }
}

// ============================
// УПРАВЛЕНИЕ КОРЗИНОЙ
// ============================

function openCart() {
    document.getElementById('cartModal').classList.add('active');
    document.getElementById('overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    if (tg && tg.HapticFeedback) {
        tg.HapticFeedback.impactOccurred('soft');
    }
}

function closeCart() {
    document.getElementById('cartModal').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// ============================
// ОФОРМЛЕНИЕ ЗАКАЗА
// ============================

async function checkout() {
    if (cart.length === 0) return;
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const orderDetails = cart.map(item => 
        `• ${item.name} × ${item.quantity} = ${(item.price * item.quantity).toLocaleString()} ₽`
    ).join('\n');
    
    const orderData = {
        type: 'new_order',
        user: user ? {
            id: user.id,
            name: user.first_name,
            username: user.username,
            language_code: user.language_code
        } : { id: 'anonymous' },
        cart: cart,
        total: total,
        items_count: cart.reduce((sum, item) => sum + item.quantity, 0),
        timestamp: new Date().toISOString(),
        order_id: 'ORD-' + Date.now()
    };
    
    if (tg && tg.showPopup) {
        tg.showPopup({
            title: '✅ Подтверждение заказа',
            message: `Заказ на сумму ${total.toLocaleString()} ₽\n\n${orderDetails}\n\nМы свяжемся с вами в Telegram для уточнения деталей доставки.`,
            buttons: [
                {type: 'default', text: '✅ Подтвердить', id: 'confirm'},
                {type: 'cancel', text: '❌ Отменить'}
            ]
        }).then(btnId => {
            if (btnId === 'confirm') {
                // Отправляем заказ в API
                createOrderInAPI(orderData);
                
                tg.sendData(JSON.stringify(orderData));
                
                showNotification('🎉 Заказ успешно оформлен!');
                
                cart = [];
                saveCart();
                updateCartUI();
                closeCart();
                
                if (tg.HapticFeedback) {
                    tg.HapticFeedback.notificationOccurred('success');
                }
                
                setTimeout(() => {
                    tg.showAlert('Спасибо за заказ! Мы свяжемся с вами в ближайшее время для подтверждения.');
                }, 500);
            }
        });
    } else {
        // Для тестирования в браузере
        const confirmed = confirm(`Оформить заказ на сумму ${total.toLocaleString()} ₽?\n\n${orderDetails}`);
        if (confirmed) {
            createOrderInAPI(orderData);
            showNotification('🎉 Заказ успешно оформлен!');
            cart = [];
            saveCart();
            updateCartUI();
            closeCart();
        }
    }
}

async function createOrderInAPI(orderData) {
    if (USE_DEMO_DATA) return; // Пропускаем для демо-режима
    
    try {
        const response = await fetch(`${API_BASE_URL}/?action=create_order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        if (!data.success) {
            console.error('Ошибка сохранения заказа:', data.message);
        }
    } catch (error) {
        console.error('Ошибка отправки заказа:', error);
    }
}

// ============================
// АДМИН-ФУНКЦИИ
// ============================

function addProduct() {
    if (tg && tg.showPopup) {
        tg.showPopup({
            title: '➕ Добавить товар',
            message: 'Эта функция находится в разработке. В следующем обновлении вы сможете добавлять товары через удобный интерфейс.',
            buttons: [{type: 'ok', text: 'Понятно'}]
        });
    } else {
        alert('Функция добавления товара находится в разработке');
    }
}

function manageProducts() {
    if (tg && tg.showPopup) {
        tg.showPopup({
            title: '📦 Управление товарами',
            message: 'Админ-панель для управления товарами будет доступна в следующем обновлении.',
            buttons: [{type: 'ok', text: 'ОК'}]
        });
    } else {
        alert('Панель управления товарами в разработке');
    }
}

function viewOrders() {
    if (tg && tg.showPopup) {
        tg.showPopup({
            title: '📋 Просмотр заказов',
            message: 'Система отслеживания заказов будет реализована после подключения базы данных.',
            buttons: [{type: 'ok', text: 'Ясно'}]
        });
    } else {
        alert('Просмотр заказов в разработке');
    }
}

// ============================
// ОБРАБОТЧИКИ СОБЫТИЙ
// ============================

document.getElementById('overlay').addEventListener('click', closeCart);

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('cartModal').classList.contains('active')) {
        closeCart();
    }
});

// Закрытие корзины при свайпе
let touchStartX = 0;
const cartModal = document.getElementById('cartModal');
if (cartModal) {
    cartModal.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    cartModal.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 100) {
            closeCart();
        }
    });
}
