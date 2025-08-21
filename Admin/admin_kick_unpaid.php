<?php
// Use require_once and adjust path to go up one level
require_once '../database/database.php';
session_start();

// Create an instance of the Database class
$db = new Database();

// --- Authentication ---
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$msg = "";
$error = "";

// --- Handle POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kick_invoice_id'])) {
    $invoice_id = intval($_POST['kick_invoice_id']);
    $client_id = intval($_POST['kick_client_id']);
    $space_id = intval($_POST['kick_space_id']);
    $request_id = intval($_POST['kick_request_id']);

    // Call the single, transactional method to perform the kick
    if ($db->kickOverdueClient($invoice_id, $client_id, $space_id, $request_id)) {
        $msg = "Client #{$client_id} was successfully kicked from unit #{$space_id}.";
    } else {
        $error = "An error occurred. The client could not be kicked. Please check server logs.";
    }
}

// --- Fetch Data for Display ---
$overdue_rentals = $db->getOverdueRentalsForKicking();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kick Unpaid Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
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
<div class="container my-5">
    <h2 class="mb-4">Kick Unpaid Clients</h2>
    
    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-dark align-middle">
            <thead>
                <tr>
                    <th>Client</th><th>Unit</th><th>Invoice Date</th><th>Rental End</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($overdue_rentals)): ?>
                <?php foreach ($overdue_rentals as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Client_fn'] . ' ' . $row['Client_ln']) ?> (ID: <?= $row['Client_ID'] ?>)</td>
                        <td><?= htmlspecialchars($row['SpaceName']) ?> (ID: <?= $row['Space_ID'] ?>)</td>
                        <td><?= htmlspecialchars($row['InvoiceDate']) ?></td>
                        <td><?= htmlspecialchars($row['EndDate']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirmAction(this);">
                                <input type="hidden" name="kick_invoice_id" value="<?= $row['Invoice_ID'] ?>">
                                <input type="hidden" name="kick_client_id" value="<?= $row['Client_ID'] ?>">
                                <input type="hidden" name="kick_space_id" value="<?= $row['Space_ID'] ?>">
                                <input type="hidden" name="kick_request_id" value="<?= $row['Request_ID'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Kick & Free Unit</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">No overdue unpaid clients found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
    function confirmAction(form) {
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: "This will terminate the client's rental and make the unit available. This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, kick this client!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        // Prevent the default form submission, wait for SweetAlert
        return false;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>