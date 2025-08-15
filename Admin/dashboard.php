<?php
session_start();
require '../database/database.php';

$db = new Database();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Soft delete logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete_msg_id'])) {
    $msg_id = intval($_POST['soft_delete_msg_id']);
    $db->executeStatement("UPDATE free_message SET is_deleted = 1 WHERE Message_ID = ?", [$msg_id]);
    // Redirect to avoid resubmission and keep the same filter
    $filterParam = isset($_GET['filter']) ? 'filter=' . urlencode($_GET['filter']) : '';
    header("Location: dashboard.php" . ($filterParam ? "?$filterParam" : ""));
    exit();
}

// Dashboard counts, rental requests, etc.
$counts = $db->getAdminDashboardCounts();
$pending = $counts['pending_rentals'] ?? 0;
$pending_maintenance = $counts['pending_maintenance'] ?? 0;
$unpaid_invoices = $counts['unpaid_invoices'] ?? 0;
$unpaid_due_invoices = $counts['unpaid_due_invoices'] ?? 0;
$latest_requests = $db->getLatestPendingRequests(5);

// Message filter logic
$filter = $_GET['filter'] ?? 'recent';
if ($filter === 'all') {
    $free_messages = $db->getAllFreeMessages();
} else {
    $free_messages = $db->getRecentFreeMessages(5);
}

function timeAgo($datetime) {
    $sent = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($sent);

    if ($diff->days > 0) return $diff->days . ' days ago';
    if ($diff->h > 0) return $diff->h . ' hours ago';
    if ($diff->i > 0) return $diff->i . ' minutes ago';
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #121212, #1c1c1c);
            color: #fff;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: rgba(20, 20, 20, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.6);
        }
        .sidebar h4 {
            font-weight: 700;
            letter-spacing: 1px;
            color: #00f0ff;
        }
        .sidebar a {
            display: block;
            color: #ddd;
            padding: 15px;
            margin-bottom: 5px;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid #00f0ff;
            color: #00f0ff;
        }
        .nav-icon {
            margin-right: 10px;
        }
        .content {
            padding: 30px;
            background: #181818;
        }
        .welcome {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #0ef;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: scale(1.02);
        }
        .card .card-title {
            font-weight: 600;
        }
        .badge {
            border-radius: 8px;
            font-size: 0.75rem;
            padding: 6px 10px;
        }
        .bg-danger {
            background: #ff4d4d !important;
            color: #fff;
            box-shadow: 0 0 8px #ff4d4d, 0 0 15px #ff4d4d;
        }
        .bg-warning {
            background: #ffc107 !important;
            color: #000;
        }
        .bg-info {
            background: #00f0ff !important;
            color: #000;
            box-shadow: 0 0 8px #00f0ff, 0 0 15px #00f0ff;
        }
        .table th, .table td {
            color: #fff;
            background-color: rgba(0, 0, 0, 0.2);
        }
        .table thead {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 255, 255, 0.05);
        }
        /* Message Board */
        .msg-board {
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 18px 14px;
            margin-top: 40px;
            margin-bottom: 24px;
            max-height: 370px;
            overflow-y: auto;
        }
        .msg-user {
            color: #0ef;
        }
        .msg-meta {
            font-size: 0.88em;
            color: #aad;
        }
        .opacity-50 {
            opacity: 0.5;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h4 class="text-white text-center mb-4"><i class="fas fa-crown"></i> Admin</h4>
        <a href="manage_user.php"><i class="fas fa-users nav-icon"></i>Manage Users</a>
        <a href="view_rental_requests.php"><i class="fas fa-clipboard-check nav-icon"></i>Rental Requests 
            <?= $pending > 0 ? "<span class='badge bg-danger float-end'>$pending</span>" : "" ?>
        </a>
        <a href="manage_maintenance.php"><i class="fas fa-tools nav-icon"></i>Maintenance 
            <?= $pending_maintenance > 0 ? "<span class='badge bg-warning text-dark float-end'>$pending_maintenance</span>" : "" ?>
        </a>
        <a href="generate_invoice.php"><i class="fas fa-file-invoice-dollar nav-icon"></i>Invoices 
            <?= $unpaid_invoices > 0 ? "<span class='badge bg-info text-dark float-end'>$unpaid_invoices</span>" : "" ?>
            <?php if ($unpaid_due_invoices > 0): ?>
                <span class="badge bg-danger float-end ms-1">Due: <?= $unpaid_due_invoices ?></span>
            <?php endif; ?>
        </a>
        <a href="add_unit.php"><i class="fas fa-plus-square nav-icon"></i>Add Unit</a>
        <a href="admin_add_handyman.php"><i class="fas fa-plus-square nav-icon"></i>Add Handy</a>
        <a href="admin_kick_unpaid.php"><i class="fas fa-user-slash nav-icon"></i>Overdue</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt nav-icon"></i>Logout</a>
    </div>

    <!-- Content -->
    <div class="content flex-grow-1">
        <div class="welcome animate__animated animate__fadeInDown">👋 Welcome back, Admin</div>
        <!-- KPI Cards Section -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending Rentals</h5>
                        <h2><?= $pending ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Pending Maintenance</h5>
                        <h2><?= $pending_maintenance ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Unpaid Invoices</h5>
                        <h2><?= $unpaid_invoices ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <!-- Latest Rental Requests Table -->
        <div class="card bg-dark text-white">
            <div class="card-header">
                <strong>📄 Latest Rental Requests</strong>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($latest_requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Client Name</th>
                                    <th>Unit</th>
                                    <th>Date Requested</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latest_requests as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['Request_ID']) ?></td>
                                        <td><?= htmlspecialchars($r['Client_fn'] . ' ' . $r['Client_ln']) ?></td>
                                        <td><?= htmlspecialchars($r['UnitName'] ?? $r['Name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($r['Requested_At'] ?? '') ?></td>
                                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($r['Status']) ?></span></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-3">No pending rental requests found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Free Message Mini Board (with filter, soft delete, no reply form) -->
        <div class="msg-board mt-5">
            <h5 class="mb-3"><i class="fas fa-comments"></i>Messages Request</h5>
            <div class="d-flex justify-content-end mb-3">
                <form method="get" class="d-inline">
                    <input type="hidden" name="filter" value="recent">
                    <button type="submit" class="btn btn-outline-info btn-sm <?= $filter==='recent'?'active':'' ?>">Recent</button>
                </form>
                <form method="get" class="d-inline ms-2">
                    <input type="hidden" name="filter" value="all">
                    <button type="submit" class="btn btn-outline-info btn-sm <?= $filter==='all'?'active':'' ?>">All</button>
                </form>
            </div>
            <?php if (empty($free_messages)): ?>
                <div class="text-muted">No messages yet.</div>
            <?php else: ?>
                <?php foreach ($free_messages as $msg): ?>
                    <div class="mb-3 pb-2 border-bottom border-secondary <?= ($msg['is_deleted'] ? 'opacity-50' : '') ?>">
                        <div class="msg-user">
                            <?= htmlspecialchars($msg['Client_Name']) ?>
                            <span class="msg-meta">
                                • <?= htmlspecialchars(date('M d, Y H:i', strtotime($msg['Sent_At']))) ?>
                                • <?= timeAgo($msg['Sent_At']) ?>
                                <?php if ($filter === 'all' && $msg['is_deleted']): ?>
                                    <span class="badge bg-danger ms-2">Deleted</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Email:</strong> <?= htmlspecialchars($msg['Client_Email']) ?><br>
                            <strong>Phone:</strong> <?= htmlspecialchars($msg['Client_Phone']) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($msg['Message_Text'])) ?></div>
                        <?php if (empty($msg['is_deleted']) || $msg['is_deleted'] == 0): ?>
                            <form method="post" class="mt-2">
                                <input type="hidden" name="soft_delete_msg_id" value="<?= $msg['Message_ID'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this message?')">
                                    Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>