<?php
/**
 * Orders Management Page
 * View and manage customer orders
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$filterDate = $_GET['date'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// Build query
$whereConditions = ['1=1'];
$params = [];

if ($filterDate) {
    $whereConditions[] = "DATE(created_at) = ?";
    $params[] = $filterDate;
}

if ($filterStatus) {
    $whereConditions[] = "order_status = ?";
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE $whereClause");
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// View order details
$viewOrder = null;
if (isset($_GET['view'])) {
    $orderId = intval($_GET['view']);
    
    // Get order
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $viewOrder = $orderStmt->fetch();
    
    // Get order items
    if ($viewOrder) {
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $viewOrder['items'] = $itemsStmt->fetchAll();
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>🛒 Order Management</h1>
    <div class="header-actions">
        <span class="badge badge-blue"><?= $totalOrders ?> Total Orders</span>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🔍 Filter Orders</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="orders.php" class="filter-form">
            <div class="form-group">
                <label for="date" class="form-label">Date</label>
                <input 
                    type="date" 
                    id="date" 
                    name="date" 
                    class="form-control"
                    value="<?= htmlspecialchars($filterDate) ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="orders.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Order Details Modal -->
<?php if ($viewOrder): ?>
    <div class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content-large" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>📄 Order Details - <?= e($viewOrder['order_number']) ?></h2>
                <a href="orders.php" class="close-modal">×</a>
            </div>
            <div class="modal-body">
                <div class="order-details-grid">
                    <div class="detail-item">
                        <label>Order Number:</label>
                        <strong><?= e($viewOrder['order_number']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <label>Date:</label>
                        <strong><?= formatDateTime($viewOrder['created_at']) ?></strong>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="badge badge-green"><?= ucfirst($viewOrder['order_status']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Payment Method:</label>
                        <strong><?= ucfirst($viewOrder['payment_method']) ?></strong>
                    </div>
                </div>
                
                <h3 class="section-title">📦 Order Items</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Points</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewOrder['items'] as $item): ?>
                            <tr>
                                <td>
                                    <img src="../<?= e($item['image']) ?>" 
                                         alt="<?= e($item['product_name']) ?>" 
                                         class="product-thumb"
                                         onerror="this.src='../images/placeholder.jpg'">
                                </td>
                                <td><strong><?= e($item['product_name']) ?></strong></td>
                                <td><?= $item['quantity'] ?>x</td>
                                <td><?= formatCurrency($item['unit_price']) ?></td>
                                <td><span class="badge badge-gold"><?= $item['unit_points'] ?> pts</span></td>
                                <td><strong><?= formatCurrency($item['subtotal']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="5" style="text-align: right;"><strong>TOTAL:</strong></td>
                            <td><strong><?= formatCurrency($viewOrder['total_amount']) ?></strong></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="5" style="text-align: right;"><strong>POINTS EARNED:</strong></td>
                            <td><span class="badge badge-gold" style="font-size: 1.2rem;"><?= $viewOrder['total_points'] ?> pts</span></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="modal-actions">
                    <button onclick="printReceipt()" class="btn btn-primary">🖨️ Print Receipt</button>
                    <a href="orders.php" class="btn btn-secondary">Close</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Orders List -->
<br>
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 Orders List</h2>
        <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="🔍 Search orders..." 
            onkeyup="searchTable()"
        >
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <p class="no-data">No orders found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date & Time</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            // Get item count
                            $itemCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
                            $itemCountStmt->execute([$order['id']]);
                            $itemCount = $itemCountStmt->fetch()['count'];
                            ?>
                            <tr>
                                <td><strong><?= e($order['order_number']) ?></strong></td>
                                <td><?= formatDateTime($order['created_at']) ?></td>
                                <td><?= $itemCount ?> items</td>
                                <td><strong><?= formatCurrency($order['total_amount']) ?></strong></td>
                                <td><span class="badge badge-gold"><?= $order['total_points'] ?> pts</span></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'completed' => 'badge-green',
                                        'pending' => 'badge-warning',
                                        'cancelled' => 'badge-danger'
                                    ];
                                    $badgeClass = $statusColors[$order['order_status']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?view=<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        👁️ View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $filterDate ? "&date=$filterDate" : '' ?><?= $filterStatus ? "&status=$filterStatus" : '' ?>" 
                           class="btn btn-sm btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $filterDate ? "&date=$filterDate" : '' ?><?= $filterStatus ? "&status=$filterStatus" : '' ?>" 
                           class="btn btn-sm btn-secondary">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('ordersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

function closeModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        window.location.href = 'orders.php';
    }
}

function printReceipt() {
    window.print();
}
</script>

<style>
.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 2rem;
}

.modal-content-large {
    background: white;
    border-radius: 20px;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-header {
    padding: 2rem;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: var(--primary-dark);
}

.close-modal {
    font-size: 2rem;
    color: #999;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-modal:hover {
    color: #333;
}

.modal-body {
    padding: 2rem;
}

.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.detail-item label {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.section-title {
    font-size: 1.3rem;
    color: var(--primary-dark);
    margin: 2rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f0f0f0;
}

.total-row {
    background: #f8f9fa;
    font-size: 1.1rem;
}

.modal-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
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

@media print {
    .sidebar, .navbar, .modal-actions, .btn, .pagination {
        display: none !important;
    }
    
    .modal-overlay {
        position: static;
        background: white;
    }
    
    .modal-content-large {
        max-width: 100%;
        max-height: none;
        box-shadow: none;
    }
}
</style>

<?php include 'includes/footer.php'; ?>