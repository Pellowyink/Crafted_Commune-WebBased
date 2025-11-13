/**
 * Crafted Commune Café - Complete JavaScript
 * Handles page navigation, carousel, category switching, order management, and points system
 */

/**
 * Enhanced Crafted Commune Café JavaScript
 * With Multi-Step Loyalty Program Checkout
 */

// ========================================
// STATE MANAGEMENT
// ========================================
let currentCategory = 'coffee';
let orderItems = [];
let currentSlide = 0;
let currentPage = 'home';
let checkoutState = {
    totalAmount: 0,
    totalPoints: 0,
    cashReceived: 0,
    change: 0,
    memberId: null,
    memberEmail: null,
    memberName: null,
    isLoyaltyMember: false
};

// ========================================
// DOM ELEMENTS
// ========================================
const pages = {
    home: document.getElementById('homePage'),
    menu: document.getElementById('menuPage'),
    about: document.getElementById('aboutPage'),
    contact: document.getElementById('contactPage')
};

const navButtons = {
    home: document.getElementById('homeBtn'),
    menu: document.getElementById('menuBtn'),
    about: document.getElementById('aboutBtn'),
    contact: document.getElementById('contactBtn')
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

// Carousel elements
const carouselTrack = document.getElementById('carouselTrack');
const carouselDots = document.getElementById('carouselDots');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    setupPageNavigation();
    loadCategory(currentCategory);
    setupCategoryButtons();
    setupReceiptListeners();
    showPage('home');
    createLoyaltyModals();
});

// ========================================
// PAGE NAVIGATION
// ========================================
function showPage(pageName) {
    Object.values(pages).forEach(page => {
        if (page) page.classList.remove('active');
    });
    
    Object.values(navButtons).forEach(btn => {
        if (btn) btn.classList.remove('active');
    });
    
    if (pages[pageName]) {
        pages[pageName].classList.add('active');
    }
    
    if (navButtons[pageName]) {
        navButtons[pageName].classList.add('active');
    }
    
    currentPage = pageName;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setupPageNavigation() {}
window.showPage = showPage;

// ========================================
// CAROUSEL FUNCTIONALITY
// ========================================
function initCarousel() {
    if (!carouselTrack || !carouselDots) return;
    
    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.className = 'carousel-dot';
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(i));
        carouselDots.appendChild(dot);
    }
    
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    
    setInterval(nextSlide, 5000);
}

function goToSlide(index) {
    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const dots = carouselDots.querySelectorAll('.carousel-dot');
    const totalSlides = slides.length;
    
    if (index < 0) index = totalSlides - 1;
    if (index >= totalSlides) index = 0;
    
    currentSlide = index;
    carouselTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
    
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === currentSlide);
    });
}

function nextSlide() { goToSlide(currentSlide + 1); }
function prevSlide() { goToSlide(currentSlide - 1); }

// ========================================
// CATEGORY SWITCHING
// ========================================
function setupCategoryButtons() {
    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const category = item.getAttribute('data-category');
            menuItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            loadCategory(category);
        });
    });
}

function loadCategory(category) {
    currentCategory = category;
    const categoryData = menuData[category];
    
    if (!categoryData) return;
    
    if (categoryTitle) categoryTitle.textContent = categoryData.title;
    if (itemCount) itemCount.textContent = `${categoryData.products.length} items`;
    
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
    
    card.addEventListener('click', () => addToOrder(product));
    return card;
}

// ========================================
// ORDER MANAGEMENT
// ========================================
function addToOrder(product) {
    const existingItem = orderItems.find(item => item.name === product.name);
    
    if (existingItem) {
        existingItem.qty++;
    } else {
        orderItems.push({
            name: product.name,
            price: product.price,
            points: product.points,
            qty: 1
        });
    }
    
    updateReceipt();
    
    if (receiptPanel && !receiptPanel.classList.contains('open')) {
        openReceipt();
    }
}

function removeFromOrder(itemName) {
    const index = orderItems.findIndex(item => item.name === itemName);
    if (index !== -1) {
        orderItems.splice(index, 1);
    }
    updateReceipt();
    if (orderItems.length === 0) {
        closeReceiptPanel();
    }
}

function updateReceipt() {
    if (!receiptItems) return;
    
    receiptItems.innerHTML = '';
    
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
    
    if (payBtn) payBtn.disabled = false;
    
    let total = 0;
    let points = 0;
    
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
        
        const removeBtn = itemElement.querySelector('.remove-btn');
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removeFromOrder(item.name);
        });
        
        receiptItems.appendChild(itemElement);
    });
    
    if (totalAmount) totalAmount.textContent = `₱${total}`;
    if (totalPoints) totalPoints.textContent = `${points} pts`;
    
    // Store in checkout state
    checkoutState.totalAmount = total;
    checkoutState.totalPoints = points;
}

// ========================================
// RECEIPT PANEL CONTROLS
// ========================================
function setupReceiptListeners() {
    if (closeReceipt) {
        closeReceipt.addEventListener('click', closeReceiptPanel);
    }
    
    if (payBtn) {
        payBtn.addEventListener('click', startCheckoutFlow);
    }
}

function openReceipt() {
    if (receiptPanel) receiptPanel.classList.add('open');
}

function closeReceiptPanel() {
    if (receiptPanel) receiptPanel.classList.remove('open');
}

// ========================================
// LOYALTY MODALS CREATION
// ========================================
function createLoyaltyModals() {
    const modalsHTML = `
        <!-- Step A: Payment & Change Modal -->
        <div class="loyalty-modal" id="paymentChangeModal">
            <div class="loyalty-modal-content">
                <h2>💰 Payment</h2>
                <div class="modal-section">
                    <div class="total-display">
                        <span>Total Amount:</span>
                        <span class="amount-large" id="modalTotalAmount">₱0</span>
                    </div>
                    <div class="form-group">
                        <label for="cashReceived">Cash Received:</label>
                        <input type="number" id="cashReceived" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="change-display" id="changeDisplay" style="display:none;">
                        <span>Change:</span>
                        <span class="change-amount" id="changeAmount">₱0</span>
                    </div>
                    <div class="error-message" id="paymentError" style="display:none;"></div>
                </div>
                <div class="modal-section loyalty-question">
                    <p class="question-text">Is this customer a Loyalty Member?</p>
                    <div class="button-group">
                        <button class="btn-primary" id="btnYesMember">Yes</button>
                        <button class="btn-secondary" id="btnNoMember">No</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step B: Member Lookup Modal -->
        <div class="loyalty-modal" id="memberLookupModal">
            <div class="loyalty-modal-content">
                <button class="modal-back-btn" id="backFromLookup">← Back</button>
                <h2>🔍 Find Loyalty Member</h2>
                <div class="modal-section">
                    <div class="form-group">
                        <label for="memberEmail">Customer Email: *</label>
                        <input type="email" id="memberEmail" placeholder="customer@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="memberNameVerify">Customer Name (optional):</label>
                        <input type="text" id="memberNameVerify" placeholder="For verification">
                    </div>
                    <div class="error-message" id="lookupError" style="display:none;"></div>
                    <button class="btn-primary btn-block" id="btnFindMember">
                        <span class="btn-text">Find Member</span>
                        <span class="btn-loader" style="display:none;">⏳</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step C: New Member Offer Modal -->
        <div class="loyalty-modal" id="newMemberOfferModal">
            <div class="loyalty-modal-content">
                <button class="modal-back-btn" id="backFromOffer">← Back</button>
                <h2>🎁 Join Our Loyalty Program</h2>
                <div class="modal-section">
                    <div class="benefits-list">
                        <p>✨ <strong>Earn points with every purchase!</strong></p>
                        <p>🎉 <strong>Redeem rewards and discounts</strong></p>
                        <p>⭐ <strong>Exclusive member offers</strong></p>
                    </div>
                    <p class="question-text">Would the customer like to join?</p>
                    <div class="button-group">
                        <button class="btn-primary" id="btnYesSignup">Yes, Sign Up</button>
                        <button class="btn-secondary" id="btnNoSignup">No, Continue</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step D: Member Registration Modal -->
        <div class="loyalty-modal" id="memberRegistrationModal">
            <div class="loyalty-modal-content">
                <button class="modal-back-btn" id="backFromRegistration">← Back</button>
                <h2>📝 New Member Registration</h2>
                <div class="modal-section">
                    <div class="form-group">
                        <label for="newMemberName">Customer Name: *</label>
                        <input type="text" id="newMemberName" placeholder="Juan Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="newMemberEmail">Customer Email: *</label>
                        <input type="email" id="newMemberEmail" placeholder="customer@example.com" required>
                    </div>
                    <div class="error-message" id="registrationError" style="display:none;"></div>
                    <button class="btn-primary btn-block" id="btnRegisterPay">
                        <span class="btn-text">Register & Complete Payment</span>
                        <span class="btn-loader" style="display:none;">⏳</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step E: Final Receipt Modal -->
        <div class="loyalty-modal" id="finalReceiptModal">
            <div class="loyalty-modal-content final-receipt">
                <h2>✅ Payment Successful!</h2>
                <div class="modal-section">
                    <div class="receipt-details">
                        <div class="receipt-row">
                            <span>Total Amount:</span>
                            <span class="value" id="finalTotalAmount">₱0</span>
                        </div>
                        <div class="receipt-row">
                            <span>Cash Received:</span>
                            <span class="value" id="finalCashReceived">₱0</span>
                        </div>
                        <div class="receipt-row highlight">
                            <span>Change Due:</span>
                            <span class="value" id="finalChange">₱0</span>
                        </div>
                    </div>
                    <div class="loyalty-info" id="loyaltyInfo" style="display:none;">
                        <div class="loyalty-card">
                            <h3>🎉 Loyalty Member</h3>
                            <p class="member-name" id="finalMemberName"></p>
                            <div class="points-earned">
                                <span>Points Earned Today:</span>
                                <span class="points-value" id="finalPointsEarned">0 pts</span>
                            </div>
                            <div class="points-total">
                                <span>New Total Points:</span>
                                <span class="points-value" id="finalTotalPoints">0 pts</span>
                            </div>
                        </div>
                    </div>
                    <br>
                    <button class="btn-primary btn-block" id="btnContinueShopping">Continue Shopping</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalsHTML);
    setupLoyaltyModalListeners();
}

// ========================================
// CHECKOUT FLOW
// ========================================
function startCheckoutFlow() {
    if (orderItems.length === 0) return;
    
    // Reset checkout state
    checkoutState = {
        totalAmount: orderItems.reduce((sum, item) => sum + (item.price * item.qty), 0),
        totalPoints: orderItems.reduce((sum, item) => sum + (item.points * item.qty), 0),
        cashReceived: 0,
        change: 0,
        memberId: null,
        memberEmail: null,
        memberName: null,
        isLoyaltyMember: false
    };
    
    // Show Step A: Payment Modal
    document.getElementById('modalTotalAmount').textContent = `₱${checkoutState.totalAmount}`;
    document.getElementById('cashReceived').value = '';
    document.getElementById('changeDisplay').style.display = 'none';
    document.getElementById('paymentError').style.display = 'none';
    showModal('paymentChangeModal');
}

function showModal(modalId) {
    // Hide all modals
    document.querySelectorAll('.loyalty-modal').forEach(m => m.classList.remove('show'));
    // Show target modal
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('show');
}

function hideAllModals() {
    document.querySelectorAll('.loyalty-modal').forEach(m => m.classList.remove('show'));
}

// ========================================
// LOYALTY MODAL EVENT LISTENERS
// ========================================
function setupLoyaltyModalListeners() {
    // Step A: Cash received calculation
    const cashInput = document.getElementById('cashReceived');
    cashInput.addEventListener('input', function() {
        const cash = parseFloat(this.value) || 0;
        checkoutState.cashReceived = cash;
        
        if (cash >= checkoutState.totalAmount) {
            checkoutState.change = cash - checkoutState.totalAmount;
            document.getElementById('changeAmount').textContent = `₱${checkoutState.change.toFixed(2)}`;
            document.getElementById('changeDisplay').style.display = 'flex';
            document.getElementById('paymentError').style.display = 'none';
        } else {
            document.getElementById('changeDisplay').style.display = 'none';
        }
    });
    
    // Step A: Yes Member button
    document.getElementById('btnYesMember').addEventListener('click', function() {
        if (!validateCashReceived()) return;
        showModal('memberLookupModal');
    });
    
    // Step A: No Member button
    document.getElementById('btnNoMember').addEventListener('click', function() {
        if (!validateCashReceived()) return;
        showModal('newMemberOfferModal');
    });
    
    // Step B: Find Member
    document.getElementById('btnFindMember').addEventListener('click', findLoyaltyMember);
    
    // Step B: Back button
    document.getElementById('backFromLookup').addEventListener('click', function() {
        showModal('paymentChangeModal');
    });
    
    // Step C: Yes Signup
    document.getElementById('btnYesSignup').addEventListener('click', function() {
        showModal('memberRegistrationModal');
    });
    
    // Step C: No Signup (skip to final)
    document.getElementById('btnNoSignup').addEventListener('click', function() {
        completeOrderWithoutLoyalty();
    });
    
    // Step C: Back button
    document.getElementById('backFromOffer').addEventListener('click', function() {
        showModal('paymentChangeModal');
    });
    
    // Step D: Register & Pay
    document.getElementById('btnRegisterPay').addEventListener('click', registerAndPay);
    
    // Step D: Back button
    document.getElementById('backFromRegistration').addEventListener('click', function() {
        showModal('newMemberOfferModal');
    });
    
    // Step E: Continue Shopping
    document.getElementById('btnContinueShopping').addEventListener('click', function() {
        hideAllModals();
        orderItems = [];
        updateReceipt();
        closeReceiptPanel();
    });
    
    // Close on outside click
    document.querySelectorAll('.loyalty-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                // Don't allow closing during checkout
                // hideAllModals();
            }
        });
    });
}

// ========================================
// VALIDATION & API CALLS
// ========================================
function validateCashReceived() {
    const cash = parseFloat(document.getElementById('cashReceived').value) || 0;
    
    if (cash < checkoutState.totalAmount) {
        document.getElementById('paymentError').textContent = 'Insufficient cash received!';
        document.getElementById('paymentError').style.display = 'block';
        return false;
    }
    
    return true;
}

function findLoyaltyMember() {
    const email = document.getElementById('memberEmail').value.trim();
    const errorDiv = document.getElementById('lookupError');
    const btn = document.getElementById('btnFindMember');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    if (!email) {
        errorDiv.textContent = 'Please enter an email address';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Show loading
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    btn.disabled = true;
    errorDiv.style.display = 'none';
    
    fetch('process_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'check_member',
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btn.disabled = false;
        
        if (data.success && data.found) {
            // Member found - proceed to complete order
            checkoutState.memberId = data.member.id;
            checkoutState.memberEmail = data.member.email;
            checkoutState.memberName = data.member.name;
            checkoutState.isLoyaltyMember = true;
            
            completeOrderWithLoyalty();
        } else {
            // Member not found - offer signup
            errorDiv.textContent = 'Member not found. Would you like to sign them up?';
            errorDiv.style.display = 'block';
            setTimeout(() => showModal('newMemberOfferModal'), 2000);
        }
    })
    .catch(error => {
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btn.disabled = false;
        errorDiv.textContent = 'Error checking member: ' + error.message;
        errorDiv.style.display = 'block';
    });
}

function registerAndPay() {
    const name = document.getElementById('newMemberName').value.trim();
    const email = document.getElementById('newMemberEmail').value.trim();
    const errorDiv = document.getElementById('registrationError');
    const btn = document.getElementById('btnRegisterPay');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    if (!name || !email) {
        errorDiv.textContent = 'Please fill in all required fields';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Show loading
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    btn.disabled = true;
    errorDiv.style.display = 'none';
    
    fetch('process_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'register_member',
            name: name,
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btn.disabled = false;
        
        if (data.success) {
            checkoutState.memberId = data.member.id;
            checkoutState.memberEmail = data.member.email;
            checkoutState.memberName = data.member.name;
            checkoutState.isLoyaltyMember = true;
            
            completeOrderWithLoyalty();
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btn.disabled = false;
        errorDiv.textContent = 'Error registering member: ' + error.message;
        errorDiv.style.display = 'block';
    });
}

function completeOrderWithLoyalty() {
    fetch('process_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'complete_order',
            items: orderItems,
            total: checkoutState.totalAmount,
            points: checkoutState.totalPoints,
            cash_received: checkoutState.cashReceived,
            member_id: checkoutState.memberId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFinalReceipt(data);
        } else {
            alert('Error completing order: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function completeOrderWithoutLoyalty() {
    fetch('process_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'complete_order',
            items: orderItems,
            total: checkoutState.totalAmount,
            points: checkoutState.totalPoints,
            cash_received: checkoutState.cashReceived,
            member_id: null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFinalReceipt(data);
        } else {
            alert('Error completing order: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function showFinalReceipt(data) {
    document.getElementById('finalTotalAmount').textContent = `₱${data.total_amount}`;
    document.getElementById('finalCashReceived').textContent = `₱${data.cash_received}`;
    document.getElementById('finalChange').textContent = `₱${data.change.toFixed(2)}`;
    
    const loyaltyInfo = document.getElementById('loyaltyInfo');
    if (data.member) {
        document.getElementById('finalMemberName').textContent = data.member.name;
        document.getElementById('finalPointsEarned').textContent = `${data.member.points_earned} pts`;
        document.getElementById('finalTotalPoints').textContent = `${data.member.new_points} pts`;
        loyaltyInfo.style.display = 'block';
    } else {
        loyaltyInfo.style.display = 'none';
    }
    
    showModal('finalReceiptModal');
}