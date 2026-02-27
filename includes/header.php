<?php
/**
 * PIERRE GASLY - Admin Header
 * Common header with sidebar navigation
 */

if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/validation-fixed.css">
    <script src="assets/js/validation-fixed.js"></script>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .sidebar-logo {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 600;
        }

        .sidebar-subtitle {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            display: block;
            padding: 14px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 30px;
        }

        .nav-item.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid white;
        }

        .nav-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
        }

        .user-role {
            font-size: 11px;
            color: rgba(255,255,255,0.75);
            opacity: 0.9;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .stat-icon {
            font-size: 36px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f5f7fa;
        }

        .stat-primary .stat-icon { background: #e3f2fd; }
        .stat-success .stat-icon { background: #e8f5e9; }
        .stat-warning .stat-icon { background: #fff3e0; }
        .stat-info .stat-icon { background: #f3e5f5; }

        .stat-details {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
        }

        .card-body {
            padding: 25px;
        }

        .btn-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: #f9f9f9;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary { background: #e3f2fd; color: #1976d2; }
        .badge-success { background: #e8f5e9; color: #388e3c; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
        .badge-danger { background: #ffebee; color: #d32f2f; }
        .badge-info { background: #f3e5f5; color: #7b1fa2; }
        .badge-secondary { background: #f5f5f5; color: #666; }

        /* Alert List */
        .alert-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #fff3e0;
            border-radius: 8px;
            border-left: 4px solid #ff9800;
        }

        .alert-icon {
            font-size: 24px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .alert-subtitle {
            font-size: 13px;
            color: #666;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-logout {
            width: 100%;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        /* Container */
        .dashboard-container {
            max-width: 1400px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">⛽</div>
            <div class="sidebar-title">Pierre Gasly</div>
            <div class="sidebar-subtitle">Admin Panel</div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'products_premium.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📦</span>
                <span>Products</span>
            </a>
            <a href="orders.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🛍️</span>
                <span>Orders</span>
            </a>
            <a href="sales.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                <span class="nav-icon">💰</span>
                <span>Sales</span>
            </a>
            <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span>Users</span>
            </a>
            <a href="ratings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'ratings.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⭐</span>
                <span>Ratings</span>
            </a>
            <a href="reports.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <span class="nav-icon">📈</span>
                <span>Reports</span>
            </a>
            <?php if (isMasterAdmin()): ?>

            <a href="rewards.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : ''; ?>">
                <span class="nav-icon">🏆</span>
                <span class="nav-label">Rewards</span>
            </a>
            <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' || basename($_SERVER['PHP_SELF']) == 'settings_improved.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span>Settings</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="user-role"><?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-logout">Logout →</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">

<!-- Custom Confirmation Popup -->
<div id="confirmPopup" class="confirm-popup">
    <div class="confirm-popup-content">
        <div class="confirm-popup-header">
            <div class="confirm-popup-title" id="confirmTitle">Confirm Action</div>
            <div class="confirm-popup-message" id="confirmMessage">Are you sure?</div>
        </div>
        <div class="confirm-popup-buttons">
            <button class="confirm-popup-btn btn-cancel" onclick="closeConfirmPopup()">Cancel</button>
            <button class="confirm-popup-btn btn-confirm" id="confirmButton" onclick="confirmAction()">Confirm</button>
        </div>
    </div>
</div>

<style>
/* Custom Confirmation Popup */
.confirm-popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.confirm-popup.active {
    display: flex;
}

.confirm-popup-content {
    background: white;
    border-radius: 20px;
    padding: 30px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: popupSlideIn 0.3s ease;
}

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.confirm-popup-header {
    margin-bottom: 20px;
}

.confirm-popup-title {
    font-size: 20px;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 8px;
}

.confirm-popup-message {
    font-size: 15px;
    color: #718096;
    line-height: 1.5;
}

.confirm-popup-buttons {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.confirm-popup-btn {
    flex: 1;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.confirm-popup-btn.btn-confirm {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.confirm-popup-btn.btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.confirm-popup-btn.btn-cancel {
    background: #e2e8f0;
    color: #4a5568;
}

.confirm-popup-btn.btn-cancel:hover {
    background: #cbd5e0;
}
</style>

<script>
let confirmCallback = null;

function showConfirmPopup(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmPopup').classList.add('active');
    confirmCallback = callback;
}

function closeConfirmPopup() {
    document.getElementById('confirmPopup').classList.remove('active');
    confirmCallback = null;
}

function confirmAction() {
    if (confirmCallback) {
        confirmCallback();
    }
    closeConfirmPopup();
}

// Override all onclick confirm dialogs
document.addEventListener('DOMContentLoaded', function() {
    // Replace all onclick="return confirm(...)" with custom popup
    document.querySelectorAll('[onclick*="confirm"]').forEach(function(element) {
        const onclickAttr = element.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes('confirm(')) {
            // Extract the confirm message
            const match = onclickAttr.match(/confirm\(['"](.+?)['"]\)/);
            if (match) {
                const message = match[1];
                const restOfCode = onclickAttr.replace(/return confirm\(.+?\);?/, '').replace(/if\s*\(!?confirm\(.+?\)\)\s*{?/, '');
                
                element.removeAttribute('onclick');
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    showConfirmPopup('Confirm Action', message, function() {
                        // Get the href if it's a link
                        if (element.tagName === 'A' && element.href) {
                            window.location.href = element.href;
                        } else if (restOfCode) {
                            eval(restOfCode);
                        }
                    });
                });
            }
        }
    });
});
</script>