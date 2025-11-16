<?php
/**
 * Product Rating Page
 * Customer rates individual products from their order
 */
require_once 'config.php';

// Get unique code from URL
$code = $_GET['code'] ?? '';

if (empty($code)) {
    die('<h1>Invalid Rating Link</h1><p>Please check your email for the correct link.</p>');
}

// Validate code and get order details
try {
    $stmt = $pdo->prepare("
        SELECT 
            rl.id as link_id,
            rl.status,
            rl.expires_at,
            rl.member_id,
            rl.order_id,
            rl.order_number,
            rl.points_earned,
            rl.total_points,
            lm.name as customer_name,
            lm.email as customer_email
        FROM rating_links rl
        JOIN loyalty_members lm ON rl.member_id = lm.id
        WHERE rl.unique_code = ?
    ");
    $stmt->execute([$code]);
    $ratingLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ratingLink) {
        die('<h1>Invalid Rating Link</h1><p>This link is invalid or has been removed.</p>');
    }
    
    // Check if expired
    if ($ratingLink['status'] === 'expired' || strtotime($ratingLink['expires_at']) < time()) {
        die('<h1>Link Expired</h1><p>This rating link has expired. Please contact us if you need assistance.</p>');
    }
    
    // Check if already completed
    $alreadyRated = $ratingLink['status'] === 'completed';
    
    // Get order items (products purchased)
    $itemsStmt = $pdo->prepare("
        SELECT 
            oi.id as order_item_id,
            oi.product_id,
            oi.product_name,
            oi.quantity,
            oi.unit_price,
            p.image,
            p.average_rating,
            p.rating_count,
            pr.rating as existing_rating
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_ratings pr ON (pr.order_item_id = oi.id AND pr.member_id = ?)
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $itemsStmt->execute([$ratingLink['member_id'], $ratingLink['order_id']]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('<h1>Database Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Products - Crafted Commune</title>
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cabin Condensed', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        h1 {
            font-family: 'Calistoga', serif;
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .points-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .points-banner h3 {
            margin-bottom: 10px;
        }
        
        .points-earned {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .product-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 20px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-card.rated {
            background: #d4edda;
            border: 2px solid #28a745;
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .product-info h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .current-rating {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stars-container {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 10px;
        }
        
        .star {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .star:hover,
        .star.active {
            color: #FFD700;
            transform: scale(1.1);
        }
        
        .rating-label {
            font-size: 0.9rem;
            color: #666;
            margin-left: 10px;
        }
        
        .rated-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        
        .submit-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.show {
            display: block;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .thank-you {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .thank-you.show {
            display: block;
        }
        
        .thank-you-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        
        .bonus-points {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border: 2px solid #ffc107;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .product-card {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
            }
            
            .star {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">☕</div>
            <h1>Crafted Commune</h1>
            <p class="subtitle">Rate Your Products</p>
        </div>
        
        <div class="points-banner">
            <h3>Thank you, <?= htmlspecialchars($ratingLink['customer_name']) ?>!</h3>
            <p>You earned <span class="points-earned"><?= $ratingLink['points_earned'] ?> points</span></p>
            <p>Total Points: <?= $ratingLink['total_points'] ?></p>
        </div>
        
        <div class="message" id="messageBox"></div>
        
        <?php if ($alreadyRated): ?>
            <div class="thank-you show">
                <div class="thank-you-icon">✅</div>
                <h2>You've already rated these products!</h2>
                <p>Thank you for your feedback!</p>
            </div>
        <?php else: ?>
            <div id="ratingForm">
                <p style="text-align: center; margin-bottom: 30px; font-size: 1.1rem;">
                    <strong>Help us improve!</strong> Rate each product you purchased:
                </p>
                
                <?php foreach ($orderItems as $item): ?>
                    <div class="product-card" data-item-id="<?= $item['order_item_id'] ?>" id="product-<?= $item['order_item_id'] ?>">
                        <img src="<?= htmlspecialchars($item['image']) ?>" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             class="product-image"
                             onerror="this.src='images/placeholder.jpg'">
                        
                        <div class="product-info">
                            <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                            <div class="product-meta">
                                Quantity: <?= $item['quantity'] ?> × ₱<?= number_format($item['unit_price'], 2) ?>
                            </div>
                            <?php if ($item['rating_count'] > 0): ?>
                                <div class="current-rating">
                                    Current Rating: <?= number_format($item['average_rating'], 1) ?> ⭐ 
                                    (<?= $item['rating_count'] ?> ratings)
                                </div>
                            <?php endif; ?>
                            
                                            <div class="stars-container" data-product-id="<?= $item['product_id'] ?>" data-item-id="<?= $item['order_item_id'] ?>">
                                <span class="star" data-rating="1">★</span>
                                <span class="star" data-rating="2">★</span>
                                <span class="star" data-rating="3">★</span>
                                <span class="star" data-rating="4">★</span>
                                <span class="star" data-rating="5">★</span>
                                <span class="rating-label"></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="submit-section">
                    <button class="submit-btn" id="submitBtn" onclick="submitRatings()">
                        Submit All Ratings
                    </button>
                    <p style="margin-top: 15px; font-size: 0.9rem; color: #666;">
                        Get 5 bonus points for rating!
                    </p>
                </div>
            </div>
            
            <div class="thank-you" id="thankYou">
                <div class="thank-you-icon">🎉</div>
                <h2>Thank You!</h2>
                <p>Your product ratings have been recorded.</p>
                <div class="bonus-points">
                    <strong>🎁 You earned 5 bonus points!</strong><br>
                    Your feedback helps us serve you better!
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const code = '<?= htmlspecialchars($code) ?>';
        const ratings = {};
        
        // Star rating interaction
        document.querySelectorAll('.stars-container').forEach(container => {
            const stars = container.querySelectorAll('.star');
            const label = container.querySelector('.rating-label');
            const itemId = container.dataset.itemId;
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratings[itemId] = rating;
                    
                    // Update stars
                    stars.forEach((s, index) => {
                        s.classList.toggle('active', index < rating);
                    });
                    
                    // Update label
                    const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                    label.textContent = labels[rating];
                    
                    // Mark card as rated
                    document.getElementById('product-' + itemId).classList.add('rated');
                });
                
                // Hover effect
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    stars.forEach((s, index) => {
                        s.style.color = index < rating ? '#FFD700' : '#ddd';
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    const currentRating = ratings[itemId] || 0;
                    stars.forEach((s, index) => {
                        s.style.color = index < currentRating ? '#FFD700' : '#ddd';
                    });
                });
            });
        });
        
        function submitRatings() {
            const totalProducts = <?= count($orderItems) ?>;
            const ratedCount = Object.keys(ratings).length;
            
            if (ratedCount === 0) {
                showMessage('Please rate at least one product!', 'error');
                return;
            }
            
            if (ratedCount < totalProducts) {
                if (!confirm(`You've only rated ${ratedCount} out of ${totalProducts} products. Submit anyway?`)) {
                    return;
                }
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            fetch('submit_product_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    ratings: ratings
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('ratingForm').style.display = 'none';
                    document.getElementById('thankYou').classList.add('show');
                } else {
                    showMessage(data.message || 'Error submitting ratings.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit All Ratings';
                }
            })
            .catch(error => {
                showMessage('Network error. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit All Ratings';
            });
        }
        
        function showMessage(text, type) {
            const messageBox = document.getElementById('messageBox');
            messageBox.textContent = text;
            messageBox.className = 'message ' + type + ' show';
            
            setTimeout(() => {
                messageBox.classList.remove('show');
            }, 5000);
        }
    </script>
</body>
</html>