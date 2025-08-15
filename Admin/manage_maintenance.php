<?php
session_start();
require '../database/database.php';

$db = new Database();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_request'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    $handyman_id = $_POST['handyman_id'] !== "" ? intval($_POST['handyman_id']) : null;

    if ($db->updateMaintenanceRequest($request_id, $status, $handyman_id)) {
        $message = '<div class="alert alert-success mb-3">Request #' . htmlspecialchars($request_id) . ' updated successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger mb-3">Failed to update request #' . htmlspecialchars($request_id) . '.</div>';
    }
}

$active_requests = $db->getActiveMaintenanceRequests();
$handyman_list = $db->getAllHandymenWithJobTypes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🛠 Manage Maintenance Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
    <h2 class="mt-3"><i class="fas fa-screwdriver-wrench me-2"></i> Maintenance Requests</h2>
    <?= $message ?>
    <div class="table-responsive">
        <table class="table table-bordered table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th><th>Client</th><th>Unit</th><th>Date</th><th>Status</th><th>Assign Handyman</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($active_requests)): ?>
                <?php foreach($active_requests as $row): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="request_id" value="<?= (int)$row['Request_ID'] ?>">
                        <td><?= $row['Request_ID'] ?></td>
                        <td><?= htmlspecialchars($row['Client_fn'] . " " . $row['Client_ln']) ?></td>
                        <td><?= htmlspecialchars($row['SpaceName']) ?></td>
                        <td><?= htmlspecialchars($row['RequestDate']) ?></td>
                        <td>
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach (['Submitted', 'In Progress', 'Completed'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $row['Status'] === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="handyman_id" class="form-select form-select-sm">
                                <option value="">None</option>
                                <?php foreach ($handyman_list as $h): ?>
                                    <option value="<?= (int)$h['Handyman_ID'] ?>" <?= $row['Handyman_ID'] == $h['Handyman_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($h['Handyman_fn'] . ' ' . $h['Handyman_ln']) ?>
                                        <?php if($h['JobTypes']): ?> (<?= htmlspecialchars($h['JobTypes']) ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($row['Handyman_fn']): ?>
                                <span class="handyman-jobtype">
                                    Currently: <?= htmlspecialchars($row['Handyman_fn'] . ' ' . $row['Handyman_ln']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="submit" name="update_request" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No active maintenance requests found.</td></tr>
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

    document.querySelector('.theme-toggle').addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        document.body.classList.toggle('light-mode');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
