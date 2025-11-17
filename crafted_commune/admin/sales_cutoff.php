<?php
/**
 * Sales Cutoff Management
 * View, create, and manage sales cutoffs
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();
$action = $_GET['action'] ?? 'list';

// Handle Create Cutoff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cutoff'])) {
    $cutoffDate = $_POST['cutoff_date'] ?? date('Y-m-d');
    $cutoffTime = $_POST['cutoff_time'] ?? date('H:i:s');
    $notes = trim($_POST['notes'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Get orders for the cutoff period
        $ordersStmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders, 
                   COALESCE(SUM(total_amount), 0) as total_sales,
                   COALESCE(SUM(total_points), 0) as total_points
            FROM orders 
            WHERE DATE(created_at) = ? 
            AND TIME(created_at) <= ?
            AND order_status = 'completed'
            AND id NOT IN (SELECT order_id FROM cutoff_orders WHERE DATE(cutoff_date) = ?)
        ");
        $ordersStmt->execute([$cutoffDate, $cutoffTime, $cutoffDate]);
        $stats = $ordersStmt->fetch();
        
        if ($stats['total_orders'] == 0) {
            throw new Exception('No new orders found for this cutoff period');
        }
        
        // Create cutoff record
        $insertStmt = $pdo->prepare("
            INSERT INTO sales_cutoffs (cutoff_date, cutoff_time, total_orders, total_sales, total_points, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $cutoffDate,
            $cutoffTime,
            $stats['total_orders'],
            $stats['total_sales'],
            $stats['total_points'],
            $notes,
            $_SESSION['admin_id']
        ]);
        $cutoffId = $pdo->lastInsertId();
        
        // Link orders to this cutoff
        $linkStmt = $pdo->prepare("
            INSERT INTO cutoff_orders (cutoff_id, order_id, cutoff_date)
            SELECT ?, id, ?
            FROM orders
            WHERE DATE(created_at) = ?
            AND TIME(created_at) <= ?
            AND order_status = 'completed'
            AND id NOT IN (SELECT order_id FROM cutoff_orders WHERE DATE(cutoff_date) = ?)
        ");
        $linkStmt->execute([$cutoffId, $cutoffDate, $cutoffDate, $cutoffTime, $cutoffDate]);
        
        $pdo->commit();
        
        logActivity($_SESSION['admin_id'], 'create_cutoff', "Created cutoff #$cutoffId for $cutoffDate");
        setFlashMessage('success', "Sales cutoff created successfully! #{$cutoffId}");
        redirect("sales_cutoff.php?action=view&id=$cutoffId");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Error creating cutoff: ' . $e->getMessage());
    }
}

// Handle Delete Cutoff
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $pdo->beginTransaction();
        
        // Delete linked orders first
        $pdo->prepare("DELETE FROM cutoff_orders WHERE cutoff_id = ?")->execute([$id]);
        
        // Delete cutoff
        $pdo->prepare("DELETE FROM sales_cutoffs WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        
        logActivity($_SESSION['admin_id'], 'delete_cutoff', "Deleted cutoff #$id");
        setFlashMessage('success', 'Cutoff deleted successfully!');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('error', 'Error deleting cutoff: ' . $e->getMessage());
    }
    redirect('sales_cutoff.php');
}

// View Single Cutoff - FULL RECEIPT PAGE
if ($action === 'view' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get cutoff details
    $stmt = $pdo->prepare("
        SELECT sc.*, au.full_name as created_by_name
        FROM sales_cutoffs sc
        LEFT JOIN admin_users au ON sc.created_by = au.id
        WHERE sc.id = ?
    ");
    $stmt->execute([$id]);
    $cutoff = $stmt->fetch();
    
    if (!$cutoff) {
        setFlashMessage('error', 'Cutoff not found');
        redirect('sales_cutoff.php');
    }
    
    // Get product breakdown
    $productsStmt = $pdo->prepare("
        SELECT 
            oi.product_name,
            SUM(oi.quantity) as quantity_sold,
            oi.unit_price,
            SUM(oi.subtotal) as total_sales
        FROM cutoff_orders co
        JOIN orders o ON co.order_id = o.id
        JOIN order_items oi ON o.id = oi.order_id
        WHERE co.cutoff_id = ?
        GROUP BY oi.product_id, oi.product_name, oi.unit_price
        ORDER BY quantity_sold DESC
    ");
    $productsStmt->execute([$id]);
    $products = $productsStmt->fetchAll();
    
    // Get order details
    $ordersStmt = $pdo->prepare("
        SELECT 
            o.order_number,
            o.created_at,
            o.total_amount,
            o.total_points,
            lm.name as customer_name,
            lm.email as customer_email
        FROM cutoff_orders co
        JOIN orders o ON co.order_id = o.id
        LEFT JOIN loyalty_members lm ON o.loyalty_member_id = lm.id
        WHERE co.cutoff_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $ordersStmt->execute([$id]);
    $orders = $ordersStmt->fetchAll();
    
    // Calculate stats
    $totalItems = array_sum(array_column($products, 'quantity_sold'));
    $avgOrder = $cutoff['total_orders'] > 0 ? $cutoff['total_sales'] / $cutoff['total_orders'] : 0;
    
    // Count loyalty members
    $loyaltyStmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.loyalty_member_id) as member_count,
            COUNT(*) - COUNT(DISTINCT o.loyalty_member_id) as guest_count
        FROM cutoff_orders co
        JOIN orders o ON co.order_id = o.id
        WHERE co.cutoff_id = ?
    ");
    $loyaltyStmt->execute([$id]);
    $loyalty = $loyaltyStmt->fetch();
    
    // DISPLAY RECEIPT (inline HTML)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sales Cutoff Receipt - #<?= $cutoff['id'] ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Courier New', monospace; padding: 20px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
            .print-buttons { text-align: center; margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { padding: 12px 30px; margin: 0 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; font-family: Arial, sans-serif; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
            .btn-print { background: #273B08; color: white; }
            .btn-download { background: #28a745; color: white; }
            .btn-close { background: #6c757d; color: white; }
            .btn:hover { opacity: 0.9; transform: translateY(-2px); }
            .receipt { border: 2px solid #273B08; padding: 30px; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 3px double #273B08; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 28px; color: #273B08; margin-bottom: 5px; }
            .header p { margin: 3px 0; font-size: 12px; }
            .cutoff-info { background: #f5f5f0; padding: 15px; margin-bottom: 20px; border-left: 4px solid #273B08; }
            .cutoff-info h2 { color: #273B08; font-size: 18px; margin-bottom: 10px; }
            .info-row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 14px; }
            .info-row strong { color: #273B08; }
            .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
            .summary-box { text-align: center; padding: 15px; border: 2px solid #273B08; background: #f5f5f0; }
            .summary-label { font-size: 11px; color: #666; margin-bottom: 5px; }
            .summary-value { font-size: 20px; font-weight: bold; color: #273B08; }
            .section { margin: 25px 0; }
            .section h3 { color: #273B08; font-size: 16px; margin-bottom: 10px; border-bottom: 2px solid #273B08; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12px; }
            th { background: #273B08; color: white; padding: 10px 8px; text-align: left; font-weight: bold; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            tr:hover { background: #f9f9f9; }
            .total-row { font-weight: bold; background: #f5f5f0; font-size: 14px; }
            .highlight { background: #fff3cd; padding: 2px 5px; border-radius: 3px; }
            .signature-area { margin-top: 40px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; }
            .signature-box { text-align: center; }
            .signature-line { border-top: 2px solid #273B08; margin-top: 40px; padding-top: 10px; font-weight: bold; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 3px double #273B08; text-align: center; font-size: 11px; }
            @media print {
                body { padding: 0; background: white; }
                .print-buttons { display: none; }
                .receipt { border: none; box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="print-buttons">
            <button onclick="window.print()" class="btn btn-print">🖨️ Print</button>
            <button onclick="window.print()" class="btn btn-download">💾 Save as PDF</button>
            <a href="sales_cutoff.php" class="btn btn-close">✖️ Back to List</a>
        </div>

        <div class="receipt">
            <div class="header">
                <h1>☕ CRAFTED COMMUNE CAFÉ</h1>
                <p>21 Aurea, Mabalacat City, Pampanga, Philippines</p>
                <p>📞 +63 912 345 6789 | ✉️ hello@craftedcommune.com</p>
                <p style="margin-top: 10px; font-weight: bold; font-size: 14px;">DAILY SALES CUTOFF RECEIPT</p>
            </div>

            <div class="cutoff-info">
                <h2>📊 Cutoff Information</h2>
                <div class="info-row"><span><strong>Cutoff ID:</strong></span><span>#<?= $cutoff['id'] ?></span></div>
                <div class="info-row"><span><strong>Date:</strong></span><span><?= formatDate($cutoff['cutoff_date']) ?></span></div>
                <div class="info-row"><span><strong>Time:</strong></span><span><?= date('g:i A', strtotime($cutoff['cutoff_time'])) ?></span></div>
                <div class="info-row"><span><strong>Notes:</strong></span><span><?= e($cutoff['notes']) ?: '-' ?></span></div>
                <div class="info-row"><span><strong>Generated:</strong></span><span><?= formatDateTime($cutoff['created_at']) ?></span></div>
                <div class="info-row"><span><strong>Created By:</strong></span><span><?= e($cutoff['created_by_name']) ?></span></div>
            </div>

            <div class="summary-grid">
                <div class="summary-box"><div class="summary-label">TOTAL ORDERS</div><div class="summary-value"><?= $cutoff['total_orders'] ?></div></div>
                <div class="summary-box"><div class="summary-label">TOTAL SALES</div><div class="summary-value">₱<?= number_format($cutoff['total_sales'], 2) ?></div></div>
                <div class="summary-box"><div class="summary-label">POINTS GIVEN</div><div class="summary-value"><?= number_format($cutoff['total_points']) ?></div></div>
                <div class="summary-box"><div class="summary-label">AVG ORDER</div><div class="summary-value">₱<?= number_format($avgOrder, 2) ?></div></div>
            </div>

            <div class="section">
                <h3>📦 Product Sales Breakdown</h3>
                <table>
                    <thead><tr><th>Product Name</th><th style="text-align: center;">Quantity Sold</th><th style="text-align: right;">Unit Price</th><th style="text-align: right;">Total Sales</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= e($product['product_name']) ?></td>
                                <td style="text-align: center;"><?= $product['quantity_sold'] ?></td>
                                <td style="text-align: right;">₱<?= number_format($product['unit_price'], 2) ?></td>
                                <td style="text-align: right;"><strong>₱<?= number_format($product['total_sales'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2">TOTAL</td>
                            <td style="text-align: right;"><?= $totalItems ?> items</td>
                            <td style="text-align: right;">₱<?= number_format($cutoff['total_sales'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>🧾 Recent Orders (Sample)</h3>
                <table>
                    <thead><tr><th>Order #</th><th>Time</th><th>Customer</th><th style="text-align: right;">Amount</th><th style="text-align: right;">Points</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= e($order['order_number']) ?></td>
                                <td><?= date('g:i A', strtotime($order['created_at'])) ?></td>
                                <td><?php if ($order['customer_name']): ?><span class="highlight"><?= e($order['customer_name']) ?></span><?php else: ?><em>Guest</em><?php endif; ?></td>
                                <td style="text-align: right;">₱<?= number_format($order['total_amount'], 2) ?></td>
                                <td style="text-align: right;"><?= $order['total_points'] ?> pts</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($cutoff['total_orders'] > 10): ?>
                            <tr><td colspan="5" style="text-align: center; color: #999; padding: 15px;">... <?= $cutoff['total_orders'] - 10 ?> more orders ...</td></tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td colspan="3">TOTAL (<?= $cutoff['total_orders'] ?> orders)</td>
                            <td style="text-align: right;">₱<?= number_format($cutoff['total_sales'], 2) ?></td>
                            <td style="text-align: right;"><?= number_format($cutoff['total_points']) ?> pts</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>💰 Payment Summary</h3>
                <table>
                    <tr><td><strong>Total Cash Sales:</strong></td><td style="text-align: right;"><strong>₱<?= number_format($cutoff['total_sales'], 2) ?></strong></td></tr>
                    <tr><td>Number of Transactions:</td><td style="text-align: right;"><?= $cutoff['total_orders'] ?></td></tr>
                    <tr><td>Loyalty Points Distributed:</td><td style="text-align: right;"><?= number_format($cutoff['total_points']) ?> points</td></tr>
                    <tr><td>Average Transaction Value:</td><td style="text-align: right;">₱<?= number_format($avgOrder, 2) ?></td></tr>
                    <tr><td>Loyalty Members:</td><td style="text-align: right;"><?= $loyalty['member_count'] ?> (<?= $cutoff['total_orders'] > 0 ? number_format(($loyalty['member_count'] / $cutoff['total_orders']) * 100, 1) : 0 ?>%)</td></tr>
                    <tr><td>Guest Customers:</td><td style="text-align: right;"><?= $loyalty['guest_count'] ?> (<?= $cutoff['total_orders'] > 0 ? number_format(($loyalty['guest_count'] / $cutoff['total_orders']) * 100, 1) : 0 ?>%)</td></tr>
                </table>
            </div>

            <div class="signature-area">
                <div class="signature-box"><div class="signature-line">Prepared By</div><p style="margin-top: 5px; font-size: 11px;">Cashier/Staff Signature</p></div>
                <div class="signature-box"><div class="signature-line">Verified By</div><p style="margin-top: 5px; font-size: 11px;">Manager Signature</p></div>
            </div>

            <div class="footer">
                <p><strong>CRAFTED COMMUNE CAFÉ</strong></p>
                <p>Where Every Cup Tells a Story</p>
                <p style="margin-top: 10px;">This is an official sales cutoff receipt. Please keep for your records.</p>
                <p style="margin-top: 5px; font-size: 10px;">Generated on <?= formatDateTime($cutoff['created_at']) ?> | Cutoff ID: #<?= $cutoff['id'] ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// List All Cutoffs
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterDate = $_GET['date'] ?? '';
$whereClause = '1=1';
$params = [];

if ($filterDate) {
    $whereClause .= ' AND DATE(cutoff_date) = ?';
    $params[] = $filterDate;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM sales_cutoffs WHERE $whereClause");
$countStmt->execute($params);
$totalCutoffs = $countStmt->fetch()['total'];
$totalPages = ceil($totalCutoffs / $perPage);

$stmt = $pdo->prepare("
    SELECT sc.*, au.full_name as created_by_name
    FROM sales_cutoffs sc
    LEFT JOIN admin_users au ON sc.created_by = au.id
    WHERE $whereClause
    ORDER BY cutoff_date DESC, cutoff_time DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$cutoffs = $stmt->fetchAll();

// Get today's stats (ONLY orders NOT yet in any cutoff)
$todayStmt = $pdo->prepare("
    SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as sales
    FROM orders
    WHERE DATE(created_at) = CURDATE()
    AND order_status = 'completed'
    AND id NOT IN (
        SELECT DISTINCT order_id 
        FROM cutoff_orders 
        WHERE DATE(cutoff_date) = CURDATE()
    )
");
$todayStmt->execute();
$todayStats = $todayStmt->fetch();

$pageTitle = "Sales Cutoff Management";
include 'includes/header.php';
?>

<div class="page-header">
    <h1>💰 Sales Cutoff Management</h1>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="toggleCutoffForm()">
            ➕ Create New Cutoff
        </button>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <div class="stat-label">Uncounted Orders</div>
            <div class="stat-value"><?= $todayStats['orders'] ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-green">
        <div class="stat-icon">💵</div>
        <div class="stat-content">
            <div class="stat-label">Current Sales</div>
            <div class="stat-value"><?= formatCurrency($todayStats['sales']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-purple">
        <div class="stat-icon">📋</div>
        <div class="stat-content">
            <div class="stat-label">Total Cutoffs</div>
            <div class="stat-value"><?= $totalCutoffs ?></div>
        </div>
    </div>
</div>

<!-- Create Cutoff Form -->
<div class="dashboard-card" id="cutoffForm" style="display: none;">
    <div class="card-header">
        <h2>➕ Create New Cutoff</h2>
        <button class="btn btn-secondary btn-sm" onclick="toggleCutoffForm()">Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="cutoff_date">Cutoff Date *</label>
                    <input type="date" id="cutoff_date" name="cutoff_date" 
                           class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="cutoff_time">Cutoff Time *</label>
                    <input type="time" id="cutoff_time" name="cutoff_time" 
                           class="form-control" value="<?= date('H:i') ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="notes">Notes (Optional)</label>
                    <input type="text" id="notes" name="notes" 
                           class="form-control" placeholder="e.g., End of morning shift">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="create_cutoff" class="btn btn-primary">
                    💾 Create Cutoff
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleCutoffForm()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filter -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🔍 Filter Cutoffs</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" class="form-control" 
                       value="<?= htmlspecialchars($filterDate) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="sales_cutoff.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Cutoffs List -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 Sales Cutoffs History</h2>
    </div>
    <div class="card-body">
        <?php if (empty($cutoffs)): ?>
            <p class="no-data">No cutoffs found. Create your first cutoff!</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Orders</th>
                            <th>Total Sales</th>
                            <th>Points</th>
                            <th>Created By</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cutoffs as $cutoff): ?>
                            <tr>
                                <td><strong>#<?= $cutoff['id'] ?></strong></td>
                                <td>
                                    <?= formatDate($cutoff['cutoff_date']) ?><br>
                                    <small><?= date('g:i A', strtotime($cutoff['cutoff_time'])) ?></small>
                                </td>
                                <td><?= $cutoff['total_orders'] ?></td>
                                <td><strong><?= formatCurrency($cutoff['total_sales']) ?></strong></td>
                                <td><span class="badge badge-gold"><?= $cutoff['total_points'] ?> pts</span></td>
                                <td><?= e($cutoff['created_by_name']) ?></td>
                                <td><?= e($cutoff['notes']) ?: '-' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="sales_cutoff.php?action=view&id=<?= $cutoff['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            👁️ View Receipt
                                        </a>
                                        <a href="sales_cutoff.php?action=delete&id=<?= $cutoff['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this cutoff?')">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $filterDate ? "&date=$filterDate" : '' ?>" 
                           class="btn btn-sm btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $filterDate ? "&date=$filterDate" : '' ?>" 
                           class="btn btn-sm btn-secondary">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleCutoffForm() {
    const form = document.getElementById('cutoffForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.filter-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: end;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.page-info {
    font-weight: 600;
    color: #666;
}
</style>

<?php include 'includes/footer.php'; ?>