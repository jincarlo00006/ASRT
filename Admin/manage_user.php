<?php
require '../database/database.php';
session_start();

$db = new Database();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$msg = "";

// --- Fetch ALL Data for Display ---
$clients = $db->getAllClientsWithAssignedUnit();
$units = $db->getAllUnitsWithRenterInfo();

// --- Handle POST Actions ---

// Nuke (PERMA DELETE) - This will delete the client, their records, and set any rented unit as available again
if (isset($_POST['nuke_client']) && isset($_POST['client_id'])) {
    $cid = intval($_POST['client_id']);
    // Find ALL units assigned to this client (supporting multi-unit clients)
    $client_unit_ids = [];
    foreach ($clients as $cl) {
        if ($cl['Client_ID'] == $cid && !empty($cl['Space_ID'])) {
            $client_unit_ids[] = $cl['Space_ID'];
        }
    }
    // Free all spaces BEFORE deleting the client
    foreach ($client_unit_ids as $space_id) {
        $db->setUnitAvailable($space_id);
    }
    // Now nuke the client (permanent delete)
    if ($db->hardDeleteClient($cid)) {
        $msg = "Client and all associated records PERMANENTLY deleted. Any rented space is now available.";
    } else {
        $msg = "Error: Could not nuke client (delete client and free space).";
    }
}

if (isset($_POST['delete_client']) && isset($_POST['client_id'])) {
    $cid = intval($_POST['client_id']);
    $client_has_unit = false;
    foreach ($clients as $cl) {
        if ($cl['Client_ID'] == $cid && !empty($cl['SpaceName'])) {
            $client_has_unit = true;
            break;
        }
    }
    if ($client_has_unit) {
        $msg = "Cannot set inactive: Client still has a rented unit assigned.";
    } else {
        if ($db->updateClientStatus($cid, 'inactive')) {
            $msg = "Client set as inactive!";
        } else {
            $msg = "Error: Could not set client as inactive.";
        }
    }
}

if (isset($_POST['activate_client']) && isset($_POST['client_id'])) {
    $cid = intval($_POST['client_id']);
    if ($db->updateClientStatus($cid, 'active')) {
        $msg = "Client reactivated!";
    } else {
        $msg = "Error: Could not reactivate client.";
    }
}

if (isset($_POST['update_price']) && isset($_POST['space_id'], $_POST['new_price'])) {
    $sid = intval($_POST['space_id']);
    $price = floatval($_POST['new_price']);
    if ($db->updateUnit_price($sid, $price)) {
        $msg = "Unit price updated!";
    } else {
        $msg = "Error: Could not update unit price.";
    }
}

if (isset($_POST['delete_unit']) && isset($_POST['space_id'])) {
    $sid = intval($_POST['space_id']);
    if ($db->isUnitRented($sid)) {
        $msg = "Cannot delete: This unit currently has a renter assigned.";
    } else {
        if ($db->hardDeleteUnit($sid)) {
            $msg = "Unit deleted successfully!";
        } else {
            $msg = "Error: Could not delete unit.";
        }
    }
}

if (isset($_POST['hard_delete_client']) && isset($_POST['client_id'])) {
    $cid = intval($_POST['client_id']);
    $client_has_unit = false;
    foreach ($clients as $cl) {
        if ($cl['Client_ID'] == $cid && !empty($cl['SpaceName'])) {
            $client_has_unit = true;
            break;
        }
    }
    if ($client_has_unit) {
        $msg = "Cannot hard delete: Client has a rented unit assigned.";
    } else {
        if ($db->hardDeleteClient($cid)) {
            $msg = "Client and all associated records have been permanently deleted.";
        } else {
            $msg = "Error: Could not delete client and their records.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🧑‍💼 Manage Users & Units</title>
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
    <h2 class="mb-4"><i class="fas fa-users"></i> Manage Users & Units</h2>
    <?php if (!empty($msg)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Clients table -->
    <h4 class="mt-4 mb-2">Clients</h4>
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Rented Unit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <?php $client_has_unit = !empty($c['SpaceName']); ?>
                <tr>
                    <td><?= htmlspecialchars($c['Client_ID']) ?></td>
                    <td><?= htmlspecialchars($c['Client_fn'].' '.$c['Client_ln']) ?></td>
                    <td><?= htmlspecialchars($c['Client_Email']) ?></td>
                    <td><?= htmlspecialchars($c['C_username']) ?></td>
                    <td>
                      <?php if (strtolower($c['Status']) === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $client_has_unit ? htmlspecialchars($c['SpaceName']) : '<span class="text-muted">None</span>' ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="client_id" value="<?= $c['Client_ID'] ?>">
                            <?php if (strtolower($c['Status']) === 'active'): ?>
                              <button type="submit" name="delete_client" class="btn btn-warning btn-sm"
                                <?= $client_has_unit ? 'disabled title="Cannot set inactive: client has a rented unit."' : 'title="Set to Inactive"' ?>>
                                <i class="fas fa-user-slash"></i>
                              </button>
                            <?php else: ?>
                              <button type="submit" name="activate_client" class="btn btn-success btn-sm" title="Reactivate"><i class="fas fa-undo"></i></button>
                            <?php endif; ?>
                        </form>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('PERMANENTLY DELETE this client and all their records (invoices, requests, etc)? This cannot be undone!');">
                            <input type="hidden" name="client_id" value="<?= $c['Client_ID'] ?>">
                            <button type="submit" name="hard_delete_client" class="btn btn-danger btn-sm"
                                <?= $client_has_unit ? 'disabled title="Cannot hard delete: client has a rented unit."' : 'title="Hard Delete"' ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('!!! NUKE !!!\\nPERMANENTLY DELETE this client and ALL their records.\\nAny space they rent will be set to available! THIS CANNOT BE UNDONE. Are you SURE?');">
                            <input type="hidden" name="client_id" value="<?= $c['Client_ID'] ?>">
                            <button type="submit" name="nuke_client" class="btn btn-dark btn-sm" title="NUKE: Hard Delete + Free Rented Space">
                                <i class="fas fa-bomb"></i> Nuke
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Units table -->
    <h4 class="mt-4 mb-2">Units</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type ID</th>
                    <th>Price</th>
                    <th>Renter</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($units as $u): 
                $has_renter = !empty($u['Client_fn']);
                $renter_name = $has_renter ? htmlspecialchars($u['Client_fn'] . ' ' . $u['Client_ln']) : '';
            ?>
                <tr>
                    <td><?= htmlspecialchars($u['Space_ID']) ?></td>
                    <td><?= htmlspecialchars($u['Name']) ?></td>
                    <td><?= htmlspecialchars($u['SpaceType_ID']) ?></td>
                    <td>
                        <form method="post" class="d-inline-flex" style="gap:4px;">
                            <input type="hidden" name="space_id" value="<?= $u['Space_ID'] ?>">
                            <input type="number" min="0" step="0.01" name="new_price" value="<?= htmlspecialchars($u['Price']) ?>" class="form-control form-control-sm" style="width:100px;" required>
                            <button type="submit" name="update_price" class="btn btn-primary btn-sm" title="Update Price"><i class="fas fa-save"></i></button>
                        </form>
                    </td>
                    <td><?= $has_renter ? $renter_name : '<span class="text-muted">None</span>' ?></td>
                    <td>
                        <form method="post" class="d-inline delete-unit-form" data-renter-name="<?= $renter_name ?>">
                            <input type="hidden" name="space_id" value="<?= $u['Space_ID'] ?>">
                            <button type="submit" name="delete_unit" class="btn btn-danger btn-sm" <?= $has_renter ? 'disabled' : '' ?> title="Delete Unit">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
    document.querySelectorAll('.delete-unit-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Permanently delete this unit and all its records? This cannot be undone!')) {
                e.preventDefault();
            }
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>