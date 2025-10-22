/**
 * Crafted Commune Café - Complete JavaScript
 * Handles page navigation, carousel, category switching, order management, and points system
 */

// ========================================
// STATE MANAGEMENT
// ========================================
let currentCategory = 'coffee';
let orderItems = []; // Array to store ordered items: {name, price, points, qty}
let currentSlide = 0;
let currentPage = 'home';

// ========================================
// DOM ELEMENTS
// ========================================
const pages = {
    home: document.getElementById('homePage'),
    menu: document.getElementById('menuPage'),
    about: document.getElementById('aboutPage')
};

const navButtons = {
    home: document.getElementById('homeBtn'),
    menu: document.getElementById('menuBtn'),
    about: document.getElementById('aboutBtn')
};

const productGrid = document.getElementById('productGrid');
const categoryTitle = document.getElementById('categoryTitle');
const itemCount = document.getElementById('itemCount');
const menuItems = document.querySelectorAll('.menu-item');
const receiptPanel = document.getElementById('receiptPanel');
const receiptItems = document.getElementById('receiptItems');
const totalAmount = document.getElementById('totalAmount');
const totalPoints = document.getElementById('totalPoints');
const closeReceipt = document.getElementById('closeReceipt');
const payBtn = document.getElementById('payBtn');
const paymentModal = document.getElementById('paymentModal');
const modalAmount = document.getElementById('modalAmount');
const modalPoints = document.getElementById('modalPoints');
const modalClose = document.getElementById('modalClose');
const modalCloseBtn = document.getElementById('modalCloseBtn');

// Carousel elements
const carouselTrack = document.getElementById('carouselTrack');
const carouselDots = document.getElementById('carouselDots');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    // Initialize carousel
    initCarousel();
    
    // Setup page navigation
    setupPageNavigation();
    
    // Load initial category (coffee) for menu page
    loadCategory(currentCategory);
    
    // Setup category button listeners
    setupCategoryButtons();
    
    // Setup receipt listeners
    setupReceiptListeners();
    
    // Setup modal listeners
    setupModalListeners();
    
    // Show home page by default
    showPage('home');
});

// ========================================
// PAGE NAVIGATION
// ========================================
function showPage(pageName) {
    // Hide all pages
    Object.values(pages).forEach(page => {
        if (page) page.classList.remove('active');
    });
    
    // Remove active class from all nav buttons
    Object.values(navButtons).forEach(btn => {
        if (btn) btn.classList.remove('active');
    });
    
    // Show selected page
    if (pages[pageName]) {
        pages[pageName].classList.add('active');
    }
    
    // Activate corresponding nav button
    if (navButtons[pageName]) {
        navButtons[pageName].classList.add('active');
    }
    
    currentPage = pageName;
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setupPageNavigation() {
    // This function is for any additional page navigation setup
    // Navigation is now handled by onclick in HTML
}

// Make showPage globally available
window.showPage = showPage;

// ========================================
// CAROUSEL FUNCTIONALITY
// ========================================
function initCarousel() {
    if (!carouselTrack || !carouselDots) return;
    
    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    
    // Create dots
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.className = 'carousel-dot';
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(i));
        carouselDots.appendChild(dot);
    }
    
    // Setup navigation buttons
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    
    // Auto-play carousel
    setInterval(nextSlide, 5000);
}

function goToSlide(index) {
    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const dots = carouselDots.querySelectorAll('.carousel-dot');
    const totalSlides = slides.length;
    
    if (index < 0) index = totalSlides - 1;
    if (index >= totalSlides) index = 0;
    
    currentSlide = index;
    
    // Move track
    carouselTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
    
    // Update dots
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === currentSlide);
    });
}

function nextSlide() {
    goToSlide(currentSlide + 1);
}

function prevSlide() {
    goToSlide(currentSlide - 1);
}

// ========================================
// CATEGORY SWITCHING
// ========================================
function setupCategoryButtons() {
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const category = item.getAttribute('data-category');
            
            // Update active button
            menuItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            // Load new category
            loadCategory(category);
        });
    });
}

function loadCategory(category) {
    currentCategory = category;
    const categoryData = menuData[category];
    
    if (!categoryData) {
        console.error('Category not found:', category);
        return;
    }
    
    // Update header
    if (categoryTitle) categoryTitle.textContent = categoryData.title;
    if (itemCount) itemCount.textContent = `${categoryData.products.length} items`;
    
    // Clear and reload product grid
    if (productGrid) {
        productGrid.innerHTML = '';
        
        categoryData.products.forEach(product => {
            const productCard = createProductCard(product);
            productGrid.appendChild(productCard);
        });
    }
}

// ========================================
// PRODUCT CARD CREATION
// ========================================
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    // Create product HTML with points display
    card.innerHTML = `
        ${product.recommended ? '<div class="recommendation-badge"></div>' : ''}
        <div class="product-image">
            <img src="${product.image}" alt="${product.name}" 
                 onerror="this.style.opacity='0.3'">
        </div>
        <div class="product-name">${product.name}</div>
        <div class="product-price">${product.price}</div>
        <div class="product-points">${product.points}</div>
    `;
    
    // Add click listener to add item to order
    card.addEventListener('click', () => {
        addToOrder(product);
    });
    
    return card;
}

// ========================================
// ORDER MANAGEMENT
// ========================================
function addToOrder(product) {
    // Check if item already exists in order
    const existingItem = orderItems.find(item => item.name === product.name);
    
    if (existingItem) {
        // Increment quantity
        existingItem.qty++;
    } else {
        // Add new item
        orderItems.push({
            name: product.name,
            price: product.price,
            points: product.points,
            qty: 1
        });
    }
    
    // Update receipt display
    updateReceipt();
    
    // Open receipt panel if not already open
    if (receiptPanel && !receiptPanel.classList.contains('open')) {
        openReceipt();
    }
}

function removeFromOrder(itemName) {
    // Find item index
    const index = orderItems.findIndex(item => item.name === itemName);
    
    if (index !== -1) {
        orderItems.splice(index, 1);
    }
    
    // Update receipt display
    updateReceipt();
    
    // Close receipt if empty
    if (orderItems.length === 0) {
        closeReceiptPanel();
    }
}

function updateReceipt() {
    if (!receiptItems) return;
    
    // Clear receipt items
    receiptItems.innerHTML = '';
    
    // Check if order is empty
    if (orderItems.length === 0) {
        receiptItems.innerHTML = `
            <div class="empty-receipt">
                <p>No items added yet</p>
                <small>Click on products to add</small>
            </div>
        `;
        if (payBtn) payBtn.disabled = true;
        if (totalAmount) totalAmount.textContent = '₱0';
        if (totalPoints) totalPoints.textContent = '0 pts';
        return;
    }
    
    // Enable pay button
    if (payBtn) payBtn.disabled = false;
    
    // Calculate total and points
    let total = 0;
    let points = 0;
    
    // Render each item
    orderItems.forEach(item => {
        const itemTotal = item.price * item.qty;
        const itemPoints = item.points * item.qty;
        total += itemTotal;
        points += itemPoints;
        
        const itemElement = document.createElement('div');
        itemElement.className = 'receipt-item';
        itemElement.innerHTML = `
            <div class="receipt-item-info">
                <div class="receipt-item-name">${item.name}</div>
                <div class="receipt-item-qty">${item.qty} × ₱${item.price}</div>
                <div class="receipt-item-points">⭐ ${itemPoints} pts</div>
            </div>
            <div class="receipt-item-actions">
                <div class="receipt-item-price">₱${itemTotal}</div>
                <button class="remove-btn" data-name="${item.name}">Remove</button>
            </div>
        `;
        
        // Add remove button listener
        const removeBtn = itemElement.querySelector('.remove-btn');
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removeFromOrder(item.name);
        });
        
        receiptItems.appendChild(itemElement);
    });
    
    // Update total and points
    if (totalAmount) totalAmount.textContent = `₱${total}`;
    if (totalPoints) totalPoints.textContent = `${points} pts`;
}

// ========================================
// RECEIPT PANEL CONTROLS
// ========================================
function setupReceiptListeners() {
    // Close button
    if (closeReceipt) {
        closeReceipt.addEventListener('click', closeReceiptPanel);
    }
    
    // Pay button
    if (payBtn) {
        payBtn.addEventListener('click', processPayment);
    }
}

function openReceipt() {
    if (receiptPanel) {
        receiptPanel.classList.add('open');
    }
}

function closeReceiptPanel() {
    if (receiptPanel) {
        receiptPanel.classList.remove('open');
    }
}

// ========================================
// PAYMENT PROCESSING
// ========================================
function processPayment() {
    if (orderItems.length === 0) return;
    
    // Calculate total and points
    const total = orderItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const points = orderItems.reduce((sum, item) => sum + (item.points * item.qty), 0);
    
    // Prepare order data
    const orderData = {
        items: orderItems,
        total: total,
        points: points
    };
    
    // Send to server
    fetch('process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update modal amounts
            if (modalAmount) modalAmount.textContent = `₱${total}`;
            if (modalPoints) modalPoints.textContent = `${points} pts`;
            
            // Show modal
            if (paymentModal) paymentModal.classList.add('show');
            
            // Clear order
            orderItems = [];
            updateReceipt();
            closeReceiptPanel();
        } else {
            alert('Error processing order: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing order. Please try again.');
    });
}

// ========================================
// MODAL CONTROLS
// ========================================
function setupModalListeners() {
    // Close button (X)
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    
    // Close button
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    
    // Click outside to close
    if (paymentModal) {
        paymentModal.addEventListener('click', (e) => {
            if (e.target === paymentModal) {
                closeModal();
            }
        });
    }
    
    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && paymentModal && paymentModal.classList.contains('show')) {
            closeModal();
        }
    });
}

function closeModal() {
    if (paymentModal) {
        paymentModal.classList.remove('show');
    }
}