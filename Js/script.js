// ==========================================
// Coffee Shop Menu - Interactive Script
// ==========================================

// DOM Elements
const productCardForms = document.querySelectorAll('.product-card-form');
const productCards = document.querySelectorAll('.product-card');
const navButtons = document.querySelectorAll('.nav-btn');
const modal = document.getElementById('orderConfirmation');
const confirmationText = document.getElementById('confirmationText');
const continueShoppingBtn = document.getElementById('continueShoppingBtn');
const closeBtn = document.querySelector('.close');

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeProductCards();
    initializeNavButtons();
    initializeModal();
    animateOnLoad();
});

// ==========================================
// PRODUCT CARD INTERACTIONS
// ==========================================

function initializeProductCards() {
    productCardForms.forEach((form, index) => {
        const card = form.querySelector('.product-card');
        
        // Prevent default form submission for demo
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            handleProductOrder(card, form);
        });

        // Add hover effects
        card.addEventListener('mouseenter', () => {
            animateProductCard(card, true);
        });

        card.addEventListener('mouseleave', () => {
            animateProductCard(card, false);
        });

        // Stagger animation
        card.style.animation = `scaleIn 0.6s ease-out ${index * 0.08}s both`;
    });
}

function handleProductOrder(card, form) {
    const productName = card.querySelector('.product-name').textContent;
    const productPrice = card.querySelector('.product-price').textContent;
    const hasRecommendation = form.classList.contains('featured');

    // Visual feedback - click animation
    card.style.transform = 'scale(0.95)';
    setTimeout(() => {
        card.style.transform = '';
    }, 150);

    // Show confirmation modal
    showOrderConfirmation(productName, productPrice, hasRecommendation);

    // Log for PHP processing
    console.log({
        product: productName,
        price: productPrice,
        recommended: hasRecommendation,
        timestamp: new Date().toLocaleString()
    });

    // In production, you would submit the form to process_order.php
    // form.submit();
}

function animateProductCard(card, isHovering) {
    if (isHovering) {
        card.style.transform = 'translateY(-10px) scale(1.02)';
    } else {
        card.style.transform = 'translateY(0) scale(1)';
    }
}

// ==========================================
// ORDER CONFIRMATION MODAL
// ==========================================

function initializeModal() {
    closeBtn.addEventListener('click', closeModal);
    continueShoppingBtn.addEventListener('click', closeModal);
    
    // Close on outside click
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
}

function showOrderConfirmation(productName, productPrice, isRecommended) {
    let message = `<strong>${productName}</strong> has been added to your cart!<br><strong>Price: ${productPrice}</strong>`;
    
    if (isRecommended) {
        message += `<br><br><span style="color: #ff4444; font-weight: bold;">⭐ RECOMMENDED PRODUCT ⭐</span>`;
    }
    
    confirmationText.innerHTML = message;
    modal.style.display = 'flex';
    
    // Add animation
    modal.style.animation = 'fadeIn 0.3s ease-out';
}

function closeModal() {
    modal.style.display = 'none';
}

// ==========================================
// NAVIGATION BUTTONS
// ==========================================

function initializeNavButtons() {
    navButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            handleNavButtonClick(e, btn);
        });
    });
}

function handleNavButtonClick(e, btn) {
    const text = btn.textContent.trim();
    console.log(`Navigation: ${text}`);
    
    // Add ripple effect
    createRipple(e, btn);
    
    // Show notification
    showNotification(`${text} feature coming soon!`);
}

function createRipple(event, element) {
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    const ripple = document.createElement('span');
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        animation: rippleAnimation 0.6s ease-out;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
}

// ==========================================
// NOTIFICATIONS
// ==========================================

function showNotification(message) {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background-color: var(--primary-green);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        font-weight: 500;
        z-index: 999;
        animation: slideInRight 0.4s ease-out;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.4s ease-out';
        setTimeout(() => notification.remove(), 400);
    }, 3000);
}

// ==========================================
// ANIMATIONS ON LOAD
// ==========================================

function animateOnLoad() {
    // Stagger animate sidebar items
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        item.style.animation = `slideInLeft 0.5s ease-out ${index * 0.1}s both`;
    });
}

// ==========================================
// KEYBOARD SHORTCUTS
// ==========================================

document.addEventListener('keydown', (e) => {
    // Press 'Escape' to close modal
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Press 'r' to scroll to recommendations
    if ((e.key === 'r' || e.key === 'R') && !modal.style.display !== 'none') {
        scrollToRecommendations();
    }
});

function scrollToRecommendations() {
    const recommended = document.querySelector('.product-card-form.featured');
    if (recommended) {
        recommended.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        highlightElement(recommended, 3000);
    }
}

function highlightElement(element, duration = 3000) {
    const originalShadow = element.style.boxShadow;
    element.style.boxShadow = '0 0 20px rgba(61, 90, 61, 0.6)';
    
    setTimeout(() => {
        element.style.boxShadow = originalShadow;
    }, duration);
}

// ==========================================
// UTILITY ANIMATIONS
// ==========================================

const styles = document.createElement('style');
styles.textContent = `
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(20px);
        }
    }

    @keyframes rippleAnimation {
        from {
            transform: scale(0);
            opacity: 1;
        }
        to {
            transform: scale(1);
            opacity: 0;
        }
    }
`;
document.head.appendChild(styles);

// ==========================================
// PHP FORM SUBMISSION (Optional)
// ==========================================

// Uncomment to enable actual PHP form submission
/*
function submitOrderToPhp(productName, productPrice) {
    const formData = new FormData();
    formData.append('product', productName);
    formData.append('price', productPrice);
    
    fetch('Php/process_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Order processed:', data);
    })
    .catch(error => console.error('Error:', error));
}
*/

console.log('☕ Coffee Shop Menu loaded successfully!');
console.log('💡 Tip: Press "r" to scroll to recommended products, or "Escape" to close modals!');