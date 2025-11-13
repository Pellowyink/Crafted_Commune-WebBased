<?php
/**
 * Loyalty Members Management Page
 * View, Edit, Delete, Manage Loyalty Members
 */
require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$flash = getFlashMessage();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $points = intval($_POST['points']);
        $expiration = $_POST['expiration'] ?: NULL;
        
        // Check if email exists
        $checkStmt = $pdo->prepare("SELECT id FROM loyalty_members WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            setFlashMessage('error', "Email '{$email}' is already registered!");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO loyalty_members (name, email, points, expiration_date, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            
            if ($stmt->execute([$name, $email, $points, $expiration])) {
                logActivity($_SESSION['admin_id'], 'add_loyalty_member', "Added member: $name ($email)");
                setFlashMessage('success', "Member '$name' added successfully!");
            } else {
                setFlashMessage('error', 'Failed to add member.');
            }
        }
        redirect('loyalty_members.php');
    }
    
    if (isset($_POST['edit_member'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $points = intval($_POST['points']);
        $expiration = $_POST['expiration'] ?: NULL;
        
        $stmt = $pdo->prepare("
            UPDATE loyalty_members 
            SET name = ?, email = ?, points = ?, expiration_date = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $email, $points, $expiration, $id])) {
            logActivity($_SESSION['admin_id'], 'edit_loyalty_member', "Edited member: $name ($email)");
            setFlashMessage('success', "Member '$name' updated successfully!");
        } else {
            setFlashMessage('error', 'Failed to update member.');
        }
        redirect('loyalty_members.php');
    }
    
    if (isset($_POST['adjust_points'])) {
        $id = intval($_POST['member_id']);
        $adjustment = intval($_POST['adjustment']);
        $reason = trim($_POST['reason']);
        
        // Get current member
        $memberStmt = $pdo->prepare("SELECT name, email, points FROM loyalty_members WHERE id = ?");
        $memberStmt->execute([$id]);
        $member = $memberStmt->fetch();
        
        if ($member) {
            $newPoints = max(0, $member['points'] + $adjustment);
            
            // Update points
            $updateStmt = $pdo->prepare("UPDATE loyalty_members SET points = ? WHERE id = ?");
            $updateStmt->execute([$newPoints, $id]);
            
            // Log transaction
            $logStmt = $pdo->prepare("
                INSERT INTO loyalty_transactions 
                (member_id, transaction_type, points_change, points_balance, description)
                VALUES (?, 'adjustment', ?, ?, ?)
            ");
            $logStmt->execute([$id, $adjustment, $newPoints, $reason]);
            
            logActivity($_SESSION['admin_id'], 'adjust_points', "Adjusted points for {$member['name']}: {$adjustment}");
            setFlashMessage('success', "Points adjusted successfully! New balance: {$newPoints} pts");
        }
        redirect('loyalty_members.php');
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT name, email FROM loyalty_members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    
    if ($member) {
        $deleteStmt = $pdo->prepare("DELETE FROM loyalty_members WHERE id = ?");
        if ($deleteStmt->execute([$id])) {
            logActivity($_SESSION['admin_id'], 'delete_loyalty_member', "Deleted member: {$member['name']} ({$member['email']})");
            setFlashMessage('success', "Member '{$member['name']}' deleted successfully!");
        }
    }
    redirect('loyalty_members.php');
}

// Handle toggle active
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE loyalty_members SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlashMessage('success', 'Member status updated!');
    }
    redirect('loyalty_members.php');
}

// Handle extend expiration
if ($action === 'extend' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("
        UPDATE loyalty_members 
        SET expiration_date = DATE_ADD(COALESCE(expiration_date, CURDATE()), INTERVAL 1 YEAR) 
        WHERE id = ?
    ");
    if ($stmt->execute([$id])) {
        setFlashMessage('success', 'Expiration extended by 1 year!');
    }
    redirect('loyalty_members.php');
}

// Get member for editing
$editMember = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM loyalty_members WHERE id = ?");
    $stmt->execute([$id]);
    $editMember = $stmt->fetch();
}

// View member details
$viewMember = null;
if ($action === 'view' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get member
    $stmt = $pdo->prepare("SELECT * FROM loyalty_members WHERE id = ?");
    $stmt->execute([$id]);
    $viewMember = $stmt->fetch();
    
    if ($viewMember) {
        // Get transaction history
        $transStmt = $pdo->prepare("
            SELECT * FROM loyalty_transactions 
            WHERE member_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $transStmt->execute([$id]);
        $viewMember['transactions'] = $transStmt->fetchAll();
        
        // Get order history
        $ordersStmt = $pdo->prepare("
            SELECT o.* FROM orders o
            WHERE o.id IN (
                SELECT DISTINCT order_id FROM loyalty_transactions WHERE member_id = ?
            )
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $ordersStmt->execute([$id]);
        $viewMember['orders'] = $ordersStmt->fetchAll();
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$searchQuery = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$whereConditions = ['1=1'];
$params = [];

if ($searchQuery) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if ($statusFilter === 'active') {
    $whereConditions[] = "is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "is_active = 0";
} elseif ($statusFilter === 'expired') {
    $whereConditions[] = "expiration_date IS NOT NULL AND expiration_date < CURDATE()";
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM loyalty_members WHERE $whereClause");
$countStmt->execute($params);
$totalMembers = $countStmt->fetch()['total'];
$totalPages = ceil($totalMembers / $perPage);

// Get members
$stmt = $pdo->prepare("
    SELECT * FROM loyalty_members 
    WHERE $whereClause 
    ORDER BY created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get summary statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_members,
        SUM(points) as total_points,
        SUM(total_purchases) as total_revenue,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_members,
        COUNT(CASE WHEN expiration_date < CURDATE() THEN 1 END) as expired_members
    FROM loyalty_members
");
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>🎁 Loyalty Members Management</h1>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="toggleForm()">
            ➕ Add New Member
        </button>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <div class="stat-label">Total Members</div>
            <div class="stat-value"><?= number_format($stats['total_members']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-content">
            <div class="stat-label">Active Members</div>
            <div class="stat-value"><?= number_format($stats['active_members']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-orange">
        <div class="stat-icon">⭐</div>
        <div class="stat-content">
            <div class="stat-label">Total Points</div>
            <div class="stat-value"><?= number_format($stats['total_points']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-purple">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($stats['total_revenue']) ?></div>
        </div>
    </div>
</div>

<!-- Add/Edit Member Form -->
<div class="form-card" id="memberForm" style="display: <?= $editMember ? 'block' : 'none' ?>">
    <div class="card-header">
        <h2><?= $editMember ? '✏️ Edit Member' : '➕ Add New Member' ?></h2>
        <button class="btn btn-secondary btn-sm" onclick="toggleForm()">Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($editMember): ?>
                <input type="hidden" name="id" value="<?= $editMember['id'] ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?= $editMember ? e($editMember['name']) : '' ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= $editMember ? e($editMember['email']) : '' ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="points" class="form-label">Initial Points</label>
                    <input 
                        type="number" 
                        id="points" 
                        name="points" 
                        class="form-control" 
                        min="0"
                        value="<?= $editMember ? $editMember['points'] : '0' ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="expiration" class="form-label">Expiration Date (Optional)</label>
                    <input 
                        type="date" 
                        id="expiration" 
                        name="expiration" 
                        class="form-control" 
                        value="<?= $editMember ? $editMember['expiration_date'] : '' ?>"
                    >
                    <small class="form-hint">Leave blank for no expiration</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="<?= $editMember ? 'edit_member' : 'add_member' ?>" class="btn btn-primary">
                    <?= $editMember ? '💾 Update Member' : '➕ Add Member' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Member Details Modal -->
<?php if ($viewMember): ?>
    <div class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content-large" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2>👤 Member Details - <?= e($viewMember['name']) ?></h2>
                <a href="loyalty_members.php" class="close-modal">×</a>
            </div>
            <div class="modal-body">
                <!-- Member Info -->
                <div class="member-info-card">
                    <div class="member-avatar">
                        <?= strtoupper(substr($viewMember['name'], 0, 2)) ?>
                    </div>
                    <div class="member-details">
                        <h3><?= e($viewMember['name']) ?></h3>
                        <p>📧 <?= e($viewMember['email']) ?></p>
                        <div class="member-badges">
                            <?php if ($viewMember['is_active']): ?>
                                <span class="badge badge-green">✅ Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">❌ Inactive</span>
                            <?php endif; ?>
                            
                            <?php if ($viewMember['expiration_date']): ?>
                                <?php if (strtotime($viewMember['expiration_date']) < time()): ?>
                                    <span class="badge badge-danger">⏰ Expired</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">📅 Expires: <?= formatDate($viewMember['expiration_date']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-blue">♾️ No Expiration</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="member-stats-grid">
                        <div class="stat-box">
                            <div class="stat-value"><?= number_format($viewMember['points']) ?></div>
                            <div class="stat-label">Points</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?= formatCurrency($viewMember['total_purchases']) ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?= $viewMember['total_orders'] ?></div>
                            <div class="stat-label">Orders</div>
                        </div>
                    </div>
                </div>
                
                <!-- Points Adjustment Form -->
                <div class="adjustment-form">
                    <h3>⚖️ Adjust Points</h3>
                    <form method="POST" action="" class="inline-form">
                        <input type="hidden" name="member_id" value="<?= $viewMember['id'] ?>">
                        <div class="form-row">
                            <input type="number" name="adjustment" placeholder="Enter points (+/-)" required>
                            <input type="text" name="reason" placeholder="Reason" required>
                            <button type="submit" name="adjust_points" class="btn btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
                
                <!-- Transaction History -->
                <h3 class="section-title">📜 Transaction History</h3>
                <?php if (empty($viewMember['transactions'])): ?>
                    <p class="no-data">No transactions yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Balance</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($viewMember['transactions'] as $trans): ?>
                                <tr>
                                    <td><?= formatDateTime($trans['created_at']) ?></td>
                                    <td>
                                        <?php
                                        $typeColors = [
                                            'earn' => 'badge-green',
                                            'redeem' => 'badge-blue',
                                            'adjustment' => 'badge-warning'
                                        ];
                                        $badgeClass = $typeColors[$trans['transaction_type']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= ucfirst($trans['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="<?= $trans['points_change'] >= 0 ? 'text-green' : 'text-red' ?>">
                                            <?= $trans['points_change'] >= 0 ? '+' : '' ?><?= $trans['points_change'] ?>
                                        </strong>
                                    </td>
                                    <td><?= $trans['points_balance'] ?> pts</td>
                                    <td><?= e($trans['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <div class="modal-actions">
                    <a href="loyalty_members.php?action=edit&id=<?= $viewMember['id'] ?>" class="btn btn-primary">✏️ Edit Member</a>
                    <a href="loyalty_members.php" class="btn btn-secondary">Close</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Search & Filters -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🔍 Search & Filter</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="loyalty_members.php" class="filter-form">
            <div class="form-group">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search by name or email..."
                    value="<?= htmlspecialchars($searchQuery) ?>"
                >
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <a href="loyalty_members.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Members List -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 Members List (<?= $totalMembers ?>)</h2>
    </div>
    <div class="card-body">
        <?php if (empty($members)): ?>
            <p class="no-data">No members found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Points</th>
                            <th>Total Spent</th>
                            <th>Orders</th>
                            <th>Status</th>
                            <th>Expiration</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <?php
                            $isExpired = $member['expiration_date'] && strtotime($member['expiration_date']) < time();
                            ?>
                            <tr class="<?= $isExpired ? 'row-expired' : '' ?>">
                                <td><?= $member['id'] ?></td>
                                <td><strong><?= e($member['name']) ?></strong></td>
                                <td><?= e($member['email']) ?></td>
                                <td><span class="badge badge-gold"><?= number_format($member['points']) ?> pts</span></td>
                                <td><?= formatCurrency($member['total_purchases']) ?></td>
                                <td><?= $member['total_orders'] ?></td>
                                <td>
                                    <?php if ($member['is_active']): ?>
                                        <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($member['expiration_date']): ?>
                                        <?php if ($isExpired): ?>
                                            <span class="badge badge-danger">Expired</span>
                                        <?php else: ?>
                                            <?= formatDate($member['expiration_date']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-blue">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatDate($member['created_at']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="loyalty_members.php?action=view&id=<?= $member['id'] ?>" 
                                           class="btn-icon btn-icon-blue" 
                                           title="View Details">
                                            👁️
                                        </a>
                                        <a href="loyalty_members.php?action=edit&id=<?= $member['id'] ?>" 
                                           class="btn-icon btn-icon-green" 
                                           title="Edit">
                                            ✏️
                                        </a>
                                        <?php if ($isExpired): ?>
                                            <a href="loyalty_members.php?action=extend&id=<?= $member['id'] ?>" 
                                               class="btn-icon btn-icon-warning" 
                                               title="Extend Expiration">
                                                ⏰
                                            </a>
                                        <?php endif; ?>
                                        <a href="loyalty_members.php?action=toggle&id=<?= $member['id'] ?>" 
                                           class="btn-icon btn-icon-warning" 
                                           title="Toggle Active/Inactive">
                                            🔄
                                        </a>
                                        <a href="loyalty_members.php?action=delete&id=<?= $member['id'] ?>" 
                                           class="btn-icon btn-icon-danger" 
                                           onclick="return confirm('Delete member <?= e($member['name']) ?>? This cannot be undone!')"
                                           title="Delete">
                                            🗑️
                                        </a>
                                    </div>
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
                        <a href="?page=<?= $page - 1 ?><?= $searchQuery ? "&search=$searchQuery" : '' ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>" 
                           class="btn btn-sm btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $searchQuery ? "&search=$searchQuery" : '' ?><?= $statusFilter ? "&status=$statusFilter" : '' ?>" 
                           class="btn btn-sm btn-secondary">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm() {
    const form = document.getElementById('memberForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'none') {
        window.location.href = 'loyalty_members.php';
    }
}

function closeModal(event) {
    if (event.target.classList.contains('modal-overlay')) {
        window.location.href = 'loyalty_members.php';
    }
}
</script>

<style>
.member-info-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 2rem;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
}

.member-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
}

.member-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
}

.member-details p {
    margin: 0.25rem 0;
    opacity: 0.9;
}

.member-badges {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.member-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-box {
    background: rgba(255, 255, 255, 0.2);
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
}

.stat-box .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-box .stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.adjustment-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.adjustment-form h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-dark);
}

.inline-form .form-row {
    display: grid;
    grid-template-columns: 150px 1fr auto;
    gap: 1rem;
}

.inline-form input {
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
}

.text-green {
    color: #28a745;
}

.text-red {
    color: #dc3545;
}

.row-expired {
    background: #fff5f5;
}

@media (max-width: 768px) {
    .member-info-card {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .member-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .inline-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>