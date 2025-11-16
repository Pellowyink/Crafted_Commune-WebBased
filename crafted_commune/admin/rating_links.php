<?php
/**
 * Admin - Rating Links Management
 * View and manage customer rating links
 */
require_once '../config.php';
requireAdmin();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT 
        rl.*,
        lm.name as member_name,
        lm.email as member_email,
        o.order_number
    FROM rating_links rl
    JOIN loyalty_members lm ON rl.member_id = lm.id
    JOIN orders o ON rl.order_id = o.id
    WHERE 1=1
";

if ($status !== 'all') {
    $sql .= " AND rl.status = :status";
}

if (!empty($search)) {
    $sql .= " AND (lm.name LIKE :search OR lm.email LIKE :search OR rl.order_number LIKE :search)";
}

$sql .= " ORDER BY rl.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($status !== 'all') {
    $stmt->bindValue(':status', $status);
}
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}

$stmt->execute();
$ratingLinks = $stmt->fetchAll();

// Get statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_links,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count,
        SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as emails_sent
    FROM rating_links
");
$stats = $statsStmt->fetch();

$pageTitle = "Rating Links Management";
include 'includes/header.php';
?>

<div class="page-header">
    <h1>⭐ Rating Links Management</h1>
    <p>View and manage customer product rating links</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <span style="font-size: 2rem;">📧</span>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Links</div>
            <div class="stat-value"><?= number_format($stats['total_links']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <span style="font-size: 2rem;">⏳</span>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= number_format($stats['pending_count']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <span style="font-size: 2rem;">✅</span>
        </div>
        <div class="stat-info">
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= number_format($stats['completed_count']) ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <span style="font-size: 2rem;">⚠️</span>
        </div>
        <div class="stat-info">
            <div class="stat-label">Expired</div>
            <div class="stat-value"><?= number_format($stats['expired_count']) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-section">
    <h2>🔍 Search & Filter</h2>
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <input type="text" 
                       name="search" 
                       placeholder="Search by name, email, or order number..." 
                       value="<?= htmlspecialchars($search) ?>"
                       class="form-control">
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary">🔍 Search</button>
            <a href="rating_links.php" class="btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Rating Links Table -->
<div class="table-container">
    <h2>📋 Rating Links (<?= count($ratingLinks) ?>)</h2>
    
    <?php if (empty($ratingLinks)): ?>
        <div class="empty-state">
            <p>No rating links found</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Order</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th>Email Sent</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratingLinks as $link): ?>
                        <?php
                        $isExpired = strtotime($link['expires_at']) < time();
                        $statusClass = $link['status'];
                        if ($isExpired && $link['status'] !== 'completed') {
                            $statusClass = 'expired';
                        }
                        ?>
                        <tr>
                            <td><?= $link['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($link['member_name']) ?></strong><br>
                                <small><?= htmlspecialchars($link['member_email']) ?></small>
                            </td>
                            <td>
                                <a href="orders.php?search=<?= urlencode($link['order_number']) ?>" target="_blank">
                                    <?= htmlspecialchars($link['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <?= $link['points_earned'] ?> pts<br>
                                <small>Total: <?= $link['total_points'] ?></small>
                            </td>
                            <td>
                                <span class="badge badge-<?= $statusClass ?>">
                                    <?= ucfirst($link['status']) ?>
                                    <?php if ($isExpired && $link['status'] !== 'completed'): ?>
                                        (Expired)
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($link['email_sent']): ?>
                                    <span style="color: green;">✅ Sent</span>
                                <?php else: ?>
                                    <span style="color: red;">❌ Not Sent</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatDateTime($link['created_at']) ?></td>
                            <td>
                                <?= formatDateTime($link['expires_at']) ?>
                                <?php if ($isExpired && $link['status'] !== 'completed'): ?>
                                    <br><small style="color: red;">⚠️ Expired</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewLink('<?= htmlspecialchars($link['unique_code']) ?>')" 
                                            class="btn-icon" 
                                            title="View Link">
                                        👁️
                                    </button>
                                    <button onclick="copyLink('<?= htmlspecialchars($link['unique_code']) ?>')" 
                                            class="btn-icon" 
                                            title="Copy Link">
                                        📋
                                    </button>
                                    <?php if ($link['status'] === 'pending' && !$isExpired): ?>
                                        <button onclick="resendEmail(<?= $link['id'] ?>)" 
                                                class="btn-icon" 
                                                title="Resend Email">
                                            📧
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- View Link Modal -->
<div id="linkModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLinkModal()">&times;</span>
        <h2>📧 Rating Link</h2>
        <div id="linkContent">
            <p>Loading...</p>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.filters-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.filter-form {
    margin-top: 20px;
}

.form-row {
    display: flex;
    gap: 15px;
    align-items: end;
}

.form-group {
    flex: 1;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-pending {
    background: #fff3cd;
    color: #856404;
}

.badge-completed {
    background: #d4edda;
    color: #155724;
}

.badge-expired {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 5px;
    transition: transform 0.2s;
}

.btn-icon:hover {
    transform: scale(1.2);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 12px;
    width: 80%;
    max-width: 600px;
}

.close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: red;
}

#linkContent {
    margin-top: 20px;
}

.link-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    word-break: break-all;
    font-family: monospace;
    margin: 15px 0;
}

.copy-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.copy-button:hover {
    opacity: 0.9;
}
</style>

<script>
function viewLink(code) {
    const baseUrl = '<?= SITE_URL ?>';
    const fullUrl = `${baseUrl}/rate_products.php?code=${code}`;
    
    document.getElementById('linkContent').innerHTML = `
        <p><strong>Full Rating Link:</strong></p>
        <div class="link-box">${fullUrl}</div>
        <button onclick="copyToClipboard('${fullUrl}')" class="copy-button">
            📋 Copy Link
        </button>
        <p style="margin-top: 15px; color: #666;">
            <strong>Note:</strong> Send this link to the customer to rate their products.
        </p>
    `;
    
    document.getElementById('linkModal').style.display = 'block';
}

function closeLinkModal() {
    document.getElementById('linkModal').style.display = 'none';
}

function copyLink(code) {
    const baseUrl = '<?= SITE_URL ?>';
    const fullUrl = `${baseUrl}/rate_products.php?code=${code}`;
    copyToClipboard(fullUrl);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Link copied to clipboard!');
    }).catch(() => {
        alert('❌ Failed to copy link');
    });
}

function resendEmail(linkId) {
    if (!confirm('Resend rating email to this customer?')) {
        return;
    }
    
    // You can implement this feature later
    alert('⚠️ Email resend feature coming soon!');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('linkModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>