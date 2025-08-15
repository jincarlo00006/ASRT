<?php
require '../database/database.php';
session_start();

$db = new Database();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$pending_requests = $db->getPendingRentalRequests();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🏢 Pending Rental Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #181818;
            color: #fff;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #202020;
            position: fixed;
            top: 0;
            left: 0;
            transition: transform 0.3s ease-in-out;
        }
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        .sidebar a {
            display: block;
            color: #ccc;
            padding: 15px;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #333;
            color: #0ef;
        }
        .content {
            margin-left: 250px;
            padding: 30px;
            transition: margin-left 0.3s;
        }
        .toggle-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1001;
            color: #fff;
        }
        .theme-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
            cursor: pointer;
            color: #fff;
        }
        .table-dark th, .table-dark td {
            color: #fff;
        }
    </style>
</head>
<body class="dark-mode">
<div class="toggle-btn"><i class="fas fa-bars"></i></div>

<div class="sidebar">
    <h4 class="text-center text-white py-3"><i class="fas fa-crown"></i> Admin</h4>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_user.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="view_rental_requests.php"><i class="fas fa-clipboard-check"></i> Rental Requests</a>
    <a href="manage_maintenance.php"><i class="fas fa-tools"></i> Maintenance</a>
    <a href="generate_invoice.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
    <a href="add_unit.php"><i class="fas fa-plus-square"></i> Add Unit</a>
    <a href="admin_add_handyman.php"><i class="fas fa-plus-square"></i> Add Handy</a>
    <a href="admin_kick_unpaid.php"><i class="fas fa-user-slash"></i> Overdue</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2><i class="fas fa-clock me-2"></i>Pending Rental Requests</h2>
    <?php
    if (isset($_SESSION['admin_message'])) {
        echo "<script>Swal.fire('Success!', '".addslashes($_SESSION['admin_message'])."', 'success');</script>";
        unset($_SESSION['admin_message']);
    }
    if (isset($_SESSION['admin_error'])) {
        echo "<script>Swal.fire('Error!', '".addslashes($_SESSION['admin_error'])."', 'error');</script>";
        unset($_SESSION['admin_error']);
    }
    ?>
    <div class="table-responsive">
        <table class="table table-bordered table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Space</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_requests)): ?>
                    <?php foreach ($pending_requests as $row): ?>
                    <tr>
                        <td class="text-start"><?= htmlspecialchars($row['Client_fn'].' '.$row['Client_ln']) ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['StartDate']) ?></td>
                        <td><?= htmlspecialchars($row['EndDate']) ?></td>
                        <td>
                            <form method="post" action="process_request.php" class="d-inline">
                                <input type="hidden" name="request_id" value="<?= $row['Request_ID'] ?>">
                                <button name="action" value="accept" class="btn btn-success btn-sm me-1"
                                    onclick="return confirm('Are you sure you want to ACCEPT this rental request?')">
                                    <i class="fas fa-check-circle me-1"></i>Accept
                                </button>
                                <button name="action" value="reject" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to REJECT this rental request?')">
                                    <i class="fas fa-times-circle me-1"></i>Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No pending rental requests found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
