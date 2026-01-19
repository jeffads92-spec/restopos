<?php
require_once 'header.php';

// Get categories
$query_categories = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name";
$result_categories = $conn->query($query_categories);

// Get all products
$query_products = "SELECT p.*, c.category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.is_active = 1 
                   ORDER BY p.product_name";
$result_products = $conn->query($query_products);

// Get members for dropdown
$query_members = "SELECT * FROM members WHERE is_active = 1 ORDER BY member_name";
$result_members = $conn->query($query_members);
?>

<style>
/* ============================================
   RESPONSIVE POS LAYOUT - MOBILE FIRST
   FIXED: Cart mobile dengan toggle yang sempurna
   ============================================ */

.pos-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 15px;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
    min-height: calc(100vh - 80px);
}

/* Products Section */
.products-section {
    background: rgba(26, 26, 26, 0.95);
    border-radius: 20px;
    padding: 20px;
    padding-bottom: 100px; /* Space for minimized cart */
    border: 2px solid rgba(218, 165, 32, 0.3);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* Cart Section - COMPLETELY FIXED */
.cart-section {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.98), rgba(45, 45, 45, 0.98));
    backdrop-filter: blur(20px);
    border-radius: 20px 20px 0 0;
    border: 2px solid rgba(218, 165, 32, 0.3);
    border-bottom: none;
    box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5);
    z-index: 1000;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateY(calc(100% - 60px)); /* Start minimized */
}

.cart-section.expanded {
    transform: translateY(0);
}

.cart-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    border-radius: 20px 20px 0 0;
    cursor: pointer;
    flex-shrink: 0;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.cart-toggle:active {
    transform: scale(0.98);
}

.cart-toggle h5 {
    margin: 0;
    color: #0a0a0a;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cart-count-badge {
    background: #0a0a0a;
    color: #FFD700;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    min-width: 24px;
    text-align: center;
}

.cart-toggle-icon {
    color: #0a0a0a;
    font-size: 20px;
    transition: transform 0.3s ease;
}

.cart-section.expanded .cart-toggle-icon {
    transform: rotate(180deg);
}

.cart-content {
    padding: 15px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.cart-section.expanded .cart-content {
    opacity: 1;
    pointer-events: auto;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    overflow-x: auto;
    padding-bottom: 10px;
    scrollbar-width: thin;
    scrollbar-color: #DAA520 #2d2d2d;
}

.category-tab {
    padding: 12px 20px;
    border: 2px solid rgba(218, 165, 32, 0.3);
    border-radius: 12px;
    background: rgba(45, 45, 45, 0.8);
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
    font-size: 14px;
    color: #DAA520;
    white-space: nowrap;
    flex-shrink: 0;
}

.category-tab:hover {
    border-color: #DAA520;
    background: rgba(218, 165, 32, 0.2);
    color: #FFD700;
    transform: translateY(-2px);
}

.category-tab.active {
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(218, 165, 32, 0.4);
}

/* Search Box */
.search-box {
    margin-bottom: 20px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid rgba(218, 165, 32, 0.3);
    border-radius: 12px;
    font-size: 14px;
    background: rgba(45, 45, 45, 0.8);
    color: #FFD700;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: #DAA520;
    background: rgba(45, 45, 45, 0.95);
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.2);
    outline: none;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #DAA520;
    font-size: 16px;
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    max-height: calc(100vh - 400px);
    overflow-y: auto;
    padding-right: 5px;
}

.product-card {
    background: linear-gradient(135deg, rgba(45, 45, 45, 0.9), rgba(26, 26, 26, 0.9));
    border-radius: 12px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #B8860B, #DAA520, #FFD700);
}

.product-card:hover {
    border-color: #DAA520;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(218, 165, 32, 0.4);
}

.product-card:active {
    transform: scale(0.95);
}

.product-card.out-of-stock {
    opacity: 0.5;
    cursor: not-allowed;
}

.product-card.out-of-stock::after {
    content: 'HABIS';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    background: rgba(239, 68, 68, 0.9);
    color: white;
    padding: 5px 15px;
    border-radius: 5px;
    font-weight: 700;
    font-size: 12px;
    z-index: 10;
}

.product-image {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    margin: 0 auto 10px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0a0a0a;
    font-size: 28px;
    box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
}

.product-image img {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    object-fit: cover;
}

.product-name {
    font-weight: 600;
    font-size: 12px;
    color: #FFD700;
    margin-bottom: 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-price {
    color: #DAA520;
    font-weight: 700;
    font-size: 13px;
}

.product-stock {
    font-size: 10px;
    color: rgba(218, 165, 32, 0.7);
    margin-top: 3px;
}

.product-stock.low {
    color: #f59e0b;
    font-weight: 600;
}

.product-stock.out {
    color: #ef4444;
    font-weight: 700;
}

/* Member Select */
.member-select-wrapper {
    margin-bottom: 15px;
    flex-shrink: 0;
}

.form-select {
    background: rgba(45, 45, 45, 0.8);
    border: 2px solid rgba(218, 165, 32, 0.3);
    color: #FFD700;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 13px;
}

.form-select:focus {
    background: rgba(45, 45, 45, 0.95);
    border-color: #DAA520;
    color: #FFD700;
    outline: none;
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.2);
}

/* Cart Items */
.cart-items {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 15px;
    min-height: 100px;
    max-height: 35vh;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(45, 45, 45, 0.6);
    border-radius: 10px;
    margin-bottom: 8px;
    border: 1px solid rgba(218, 165, 32, 0.2);
}

.cart-item-info {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-weight: 600;
    font-size: 12px;
    color: #FFD700;
    margin-bottom: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cart-item-price {
    font-size: 11px;
    color: #DAA520;
}

/* Quantity Control */
.qty-control {
    display: flex;
    align-items: center;
    gap: 6px;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-weight: 700;
}

.qty-btn:hover {
    background: linear-gradient(135deg, #DAA520, #FFD700);
    transform: scale(1.1);
}

.qty-btn:active {
    transform: scale(0.9);
}

.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-input {
    width: 40px;
    text-align: center;
    border: 2px solid rgba(218, 165, 32, 0.3);
    border-radius: 6px;
    padding: 5px;
    font-weight: 600;
    background: rgba(45, 45, 45, 0.8);
    color: #FFD700;
    font-size: 13px;
}

.item-remove {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-remove:active {
    transform: scale(0.9);
}

/* Cart Summary */
.cart-summary {
    border-top: 2px solid rgba(218, 165, 32, 0.3);
    padding-top: 12px;
    flex-shrink: 0;
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.98), rgba(45, 45, 45, 0.98));
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 13px;
    color: #DAA520;
}

.summary-row.total {
    font-size: 16px;
    font-weight: 700;
    color: #FFD700;
    padding-top: 8px;
    border-top: 2px solid rgba(218, 165, 32, 0.3);
}

/* Payment Buttons */
.payment-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin-top: 12px;
}

.btn-payment {
    padding: 12px 10px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    white-space: nowrap;
}

.btn-payment:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-payment:active {
    transform: scale(0.95);
}

.btn-cash {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-qris {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-transfer {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-clear {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.empty-cart {
    text-align: center;
    padding: 30px 20px;
    color: rgba(218, 165, 32, 0.6);
}

.empty-cart i {
    font-size: 50px;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* Tablet & Desktop */
@media (min-width: 768px) {
    .pos-wrapper {
        flex-direction: row;
        padding: 25px;
    }
    
    .products-section {
        flex: 1;
        padding-bottom: 20px;
    }
    
    .cart-section {
        position: static;
        width: 400px;
        max-height: calc(100vh - 150px);
        border-radius: 20px;
        border: 2px solid rgba(218, 165, 32, 0.3);
        transform: translateY(0) !important;
    }
    
    .cart-toggle {
        display: none;
    }
    
    .cart-content {
        padding: 20px;
        opacity: 1 !important;
        pointer-events: auto !important;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        max-height: calc(100vh - 300px);
    }
    
    .product-image {
        width: 80px;
        height: 80px;
    }
    
    .product-name {
        font-size: 13px;
    }
    
    .product-price {
        font-size: 14px;
    }
    
    .cart-items {
        max-height: 40vh;
    }
    
    .payment-buttons {
        gap: 10px;
    }
    
    .btn-payment {
        padding: 12px;
        font-size: 13px;
    }
}

/* Large Desktop */
@media (min-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        max-height: calc(100vh - 280px);
    }
}

/* Scrollbar Styling */
.products-grid::-webkit-scrollbar,
.cart-items::-webkit-scrollbar,
.category-tabs::-webkit-scrollbar,
.cart-content::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.products-grid::-webkit-scrollbar-track,
.cart-items::-webkit-scrollbar-track,
.category-tabs::-webkit-scrollbar-track,
.cart-content::-webkit-scrollbar-track {
    background: #2d2d2d;
}

.products-grid::-webkit-scrollbar-thumb,
.cart-items::-webkit-scrollbar-thumb,
.category-tabs::-webkit-scrollbar-thumb,
.cart-content::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #B8860B, #DAA520);
    border-radius: 3px;
}
</style>

<div class="pos-wrapper">
    <!-- Products Section -->
    <div class="products-section">
        <div class="category-tabs">
            <div class="category-tab active" data-category="all">
                <i class="fas fa-th me-1"></i> Semua
            </div>
            <?php while ($cat = $result_categories->fetch_assoc()): ?>
                <div class="category-tab" data-category="<?= $cat['category_id'] ?>">
                    <i class="fas <?= $cat['icon'] ?? 'fa-tag' ?> me-1"></i> <?= $cat['category_name'] ?>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchProduct" placeholder="Cari produk...">
        </div>
        
        <div class="products-grid" id="productsGrid">
            <?php 
            $result_products->data_seek(0);
            while ($product = $result_products->fetch_assoc()): 
                $stock = intval($product['stock_quantity']);
                $min_stock = intval($product['min_stock']);
                $stock_class = '';
                $stock_label = 'Stok: ' . $stock;
                
                if ($stock <= 0) {
                    $stock_class = 'out-of-stock';
                    $stock_label = 'Habis';
                } elseif ($stock <= $min_stock) {
                    $stock_class = 'low-stock';
                }
            ?>
                <div class="product-card <?= $stock_class ?>" 
                     data-category="<?= $product['category_id'] ?>"
                     data-name="<?= strtolower($product['product_name']) ?>"
                     onclick='addToCart(<?= json_encode($product) ?>)'>
                    <?php if ($product['image'] && file_exists(UPLOAD_PATH . $product['image'])): ?>
                        <div class="product-image">
                            <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= $product['product_name'] ?>">
                        </div>
                    <?php else: ?>
                        <div class="product-image">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>
                    <div class="product-name"><?= $product['product_name'] ?></div>
                    <div class="product-price"><?= formatRupiah($product['selling_price']) ?></div>
                    <div class="product-stock <?= $stock <= 0 ? 'out' : ($stock <= $min_stock ? 'low' : '') ?>">
                        <?= $stock_label ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Cart Section - FIXED STRUCTURE -->
    <div class="cart-section" id="cartSection">
        <div class="cart-toggle" onclick="toggleCart()">
            <h5>
                <i class="fas fa-shopping-cart"></i>
                Keranjang
                <span class="cart-count-badge" id="cartCount">0</span>
            </h5>
            <i class="fas fa-chevron-up cart-toggle-icon" id="cartToggleIcon"></i>
        </div>
        
        <div class="cart-content">
            <div class="member-select-wrapper">
                <select class="form-select" id="memberSelect">
                    <option value="">Pilih Member (Optional)</option>
                    <?php while ($member = $result_members->fetch_assoc()): ?>
                        <option value="<?= $member['member_id'] ?>"><?= $member['member_name'] ?> - <?= $member['member_code'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="fas fa-shopping-bag"></i>
                    <p>Keranjang kosong</p>
                </div>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">Rp 0</span>
                </div>
                <div class="summary-row">
                    <span>Pajak (<?= TAX_RATE ?>%):</span>
                    <span id="tax">Rp 0</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total">Rp 0</span>
                </div>
                
                <div class="payment-buttons">
                    <button class="btn-payment btn-cash" onclick="processPayment('cash')">
                        <i class="fas fa-money-bill-wave"></i> Tunai
                    </button>
                    <button class="btn-payment btn-qris" onclick="processPayment('qris')">
                        <i class="fas fa-qrcode"></i> QRIS
                    </button>
                    <button class="btn-payment btn-transfer" onclick="processPayment('transfer')">
                        <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                    <button class="btn-payment btn-clear" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

// Toggle cart function - COMPLETELY FIXED
function toggleCart() {
    const cartSection = document.getElementById('cartSection');
    cartSection.classList.toggle('expanded');
}

// Category filter
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const category = this.getAttribute('data-category');
        filterProducts(category);
    });
});

// Search product
document.getElementById('searchProduct').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.getAttribute('data-name');
        card.style.display = name.includes(search) ? 'block' : 'none';
    });
});

function filterProducts(category) {
    document.querySelectorAll('.product-card').forEach(card => {
        if (category === 'all') {
            card.style.display = 'block';
        } else {
            card.style.display = card.getAttribute('data-category') === category ? 'block' : 'none';
        }
    });
}

// Add to Cart dengan validasi stok
function addToCart(product) {
    // Validasi stok habis
    if (product.stock_quantity <= 0) {
        showError('Stok produk habis!');
        return;
    }
    
    const existingItem = cart.find(item => item.product_id === product.product_id);
    
    if (existingItem) {
        // Cek apakah qty di cart sudah mencapai stok tersedia
        if (existingItem.quantity >= product.stock_quantity) {
            showError('Stok tidak mencukupi! Tersedia: ' + product.stock_quantity + ' ' + (product.unit || 'pcs'));
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            product_id: product.product_id,
            product_name: product.product_name,
            selling_price: parseFloat(product.selling_price),
            quantity: 1,
            stock_quantity: product.stock_quantity
        });
    }
    
    updateCart();
    
    // Auto expand cart on mobile when item added
    if (window.innerWidth < 768) {
        document.getElementById('cartSection').classList.add('expanded');
    }
}

function updateCart() {
    const cartItems = document.getElementById('cartItems');
    
    // Update cart count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <p>Keranjang kosong</p>
            </div>
        `;
    } else {
        cartItems.innerHTML = cart.map((item, index) => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.product_name}</div>
                    <div class="cart-item-price">${formatRupiah(item.selling_price)}</div>
                </div>
                <div class="qty-control">
                    <button class="qty-btn" onclick="decreaseQty(${index})" ${item.quantity <= 1 ? 'disabled' : ''}>
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" class="qty-input" value="${item.quantity}" 
                           onchange="updateQty(${index}, this.value)" min="1" max="${item.stock_quantity}">
                    <button class="qty-btn" onclick="increaseQty(${index})" ${item.quantity >= item.stock_quantity ? 'disabled' : ''}>
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <button class="item-remove" onclick="removeItem(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }
    
    updateSummary();
}

function increaseQty(index) {
    if (cart[index].quantity < cart[index].stock_quantity) {
        cart[index].quantity++;
        updateCart();
    } else {
        showError('Stok tidak mencukupi! Maksimal: ' + cart[index].stock_quantity);
    }
}

function decreaseQty(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        updateCart();
    }
}

function updateQty(index, value) {
    const qty = parseInt(value);
    if (qty > 0 && qty <= cart[index].stock_quantity) {
        cart[index].quantity = qty;
        updateCart();
    } else if (qty > cart[index].stock_quantity) {
        showError('Jumlah melebihi stok tersedia! Maksimal: ' + cart[index].stock_quantity);
        updateCart();
    } else {
        showError('Jumlah minimal 1!');
        updateCart();
    }
}

function removeItem(index) {
    cart.splice(index, 1);
    updateCart();
}

function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Hapus semua item dari keranjang?')) {
        cart = [];
        updateCart();
    }
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (item.selling_price * item.quantity), 0);
    const tax = subtotal * <?= TAX_RATE ?> / 100;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = formatRupiah(subtotal);
    document.getElementById('tax').textContent = formatRupiah(tax);
    document.getElementById('total').textContent = formatRupiah(total);
}

async function processPayment(method) {
    if (cart.length === 0) {
        showError('Keranjang masih kosong!');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + (item.selling_price * item.quantity), 0);
    const tax = subtotal * <?= TAX_RATE ?> / 100;
    const total = subtotal + tax;
    
    let cashReceived = total;
    
    if (method === 'cash') {
        const { value: cash } = await Swal.fire({
            title: 'Masukkan Jumlah Uang',
            input: 'number',
            inputLabel: 'Total: ' + formatRupiah(total),
            inputPlaceholder: 'Jumlah uang diterima',
            showCancelButton: true,
            confirmButtonColor: '#DAA520',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Proses',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value || parseFloat(value) < total) {
                    return 'Jumlah uang kurang!';
                }
            }
        });
        
        if (!cash) return;
        cashReceived = parseFloat(cash);
    }
    
    const memberId = document.getElementById('memberSelect').value;
    
    showLoading('Memproses transaksi...');
    
    try {
        const requestData = {
            items: cart,
            member_id: memberId || null,
            payment_method: method,
            cash_received: cashReceived,
            subtotal: subtotal,
            tax: tax,
            total: total
        };
        
        const response = await fetch('api/process_transaction.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', responseText);
            throw new Error('Server returned invalid response');
        }
        
        hideLoading();
        
        if (result.success) {
            const change = cashReceived - total;
            
            await Swal.fire({
                icon: 'success',
                title: 'Transaksi Berhasil!',
                html: `
                    <p>Kode: <strong>${result.transaction_code}</strong></p>
                    <p>Total: <strong>${formatRupiah(total)}</strong></p>
                    ${method === 'cash' ? `<p>Uang: <strong>${formatRupiah(cashReceived)}</strong></p>
                    <p>Kembalian: <strong>${formatRupiah(change)}</strong></p>` : ''}
                `,
                confirmButtonText: 'Print Struk',
                confirmButtonColor: '#DAA520'
            });
            
            window.open('print_receipt.php?id=' + result.transaction_id, '_blank');
            
            cart = [];
            document.getElementById('memberSelect').value = '';
            updateCart();
            
            // Auto minimize cart after successful payment on mobile
            if (window.innerWidth < 768) {
                document.getElementById('cartSection').classList.remove('expanded');
            }
        } else {
            showError(result.message || 'Transaksi gagal!');
        }
    } catch (error) {
        hideLoading();
        console.error('Transaction error:', error);
        showError('Terjadi kesalahan: ' + error.message);
    }
}
</script>

<?php require_once 'footer.php'; ?>