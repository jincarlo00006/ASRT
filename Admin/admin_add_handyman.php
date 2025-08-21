<?php
session_start();
require_once '../database/database.php';

$db = new Database();

// --- Authentication ---
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$edit = false;
$edit_data = ['Handyman_ID' => '', 'Handyman_fn' => '', 'Handyman_ln' => '', 'Phone' => '', 'JobType_ID' => ''];
$msg = '';

// --- Handle DELETE Request ---
if (isset($_GET['delete'])) {
    $hid = intval($_GET['delete']);
    if ($db->deleteHandyman($hid)) {
        header("Location: admin_add_handyman.php?msg=deleted");
    } else {
        header("Location: admin_add_handyman.php?msg=error");
    }
    exit;
}

// --- Handle POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new job type
    if (isset($_POST['add_jobtype'])) {
        $new_jobtype = trim($_POST['NewJobType'] ?? '');
        if (!empty($new_jobtype)) {
            if ($db->addJobType($new_jobtype)) {
                header("Location: admin_add_handyman.php?msg=jobtype_added");
                exit;
            } else {
                $msg = "Failed to add new job type.";
            }
        } else {
            $msg = "Job type name cannot be empty.";
        }
    } else {
        // Handle adding/updating a handyman
        $fn = trim($_POST['Handyman_fn'] ?? '');
        $ln = trim($_POST['Handyman_ln'] ?? '');
        $phone = trim($_POST['Phone'] ?? '');
        $jobtype_id = intval($_POST['JobType_ID'] ?? 0);

        // Use strict checks to avoid "" and 0
        if ($fn !== '' && $ln !== '' && $phone !== '' && $jobtype_id > 0) {
            if (isset($_POST['handyman_id']) && !empty($_POST['handyman_id'])) {
                // UPDATE
                $id = intval($_POST['handyman_id']);
                if ($db->updateHandyman($id, $fn, $ln, $phone, $jobtype_id)) {
                    header("Location: admin_add_handyman.php?msg=updated");
                    exit;
                } else { $msg = "Failed to update handyman."; }
            } else {
                // ADD
                if ($db->addHandyman($fn, $ln, $phone, $jobtype_id)) {
                    header("Location: admin_add_handyman.php?msg=added");
                    exit;
                } else { $msg = "Failed to add handyman."; }
            }
        } else {
            $msg = "All handyman fields are required.";
        }
    }
}

// --- Handle EDIT Request (to populate the form) ---
if (isset($_GET['edit'])) {
    $edit = true;
    $hid = intval($_GET['edit']);
    $data = $db->getHandymanById($hid);
    if ($data) {
        $edit_data = $data;
    }
}

// --- Fetch Data for Display ---
$jobtypes = $db->getAllJobTypes();
$handymen_list = $db->getAllHandymenWithJob();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Handyman Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
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
<div class="container mt-5">
    <h3>Handyman List</h3>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <?php if ($msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <table class="table table-bordered table-dark">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone</th>
                <th>Job Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($handymen_list as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['Handyman_fn']) ?></td>
                <td><?= htmlspecialchars($r['Handyman_ln']) ?></td>
                <td><?= htmlspecialchars($r['Phone']) ?></td>
                <td><?= htmlspecialchars($r['JobType_Name'] ?? '—') ?></td>
                <td>
                    <a href="?edit=<?= $r['Handyman_ID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="#" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $r['Handyman_ID'] ?>)">Delete</a>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <hr class="my-5">

    <h4><?= $edit ? 'Edit Handyman' : 'Add New Handyman' ?></h4>
    <form method="POST" class="mb-5 bg-dark text-light p-4 rounded" style="background:#232323 !important;">
        <?php if ($edit): ?>
            <input type="hidden" name="handyman_id" value="<?= $edit_data['Handyman_ID'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">First Name*</label>
            <input type="text" name="Handyman_fn" class="form-control bg-dark text-light border-secondary" required
                value="<?= htmlspecialchars($edit_data['Handyman_fn']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Last Name*</label>
            <input type="text" name="Handyman_ln" class="form-control bg-dark text-light border-secondary" required
                value="<?= htmlspecialchars($edit_data['Handyman_ln']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Phone*</label>
            <input type="text" name="Phone" class="form-control bg-dark text-light border-secondary" required
                value="<?= htmlspecialchars($edit_data['Phone']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Job Type*</label>
            <select name="JobType_ID" class="form-control bg-dark text-light border-secondary" required>
                <option value="">-- Select Job Type --</option>
                <?php foreach ($jobtypes as $jt): ?>
                    <option value="<?= $jt['JobType_ID'] ?>"
                        <?= $jt['JobType_ID'] == $edit_data['JobType_ID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($jt['JobType_Name']) ?> (ID: <?= $jt['JobType_ID'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= $edit ? 'Update' : 'Add' ?> Handyman</button>
        <?php if ($edit): ?>
            <a href="admin_add_handyman.php" class="btn btn-secondary">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <hr class="my-5">
    
    <h4>Add New Job Type</h4>
    <form method="POST" class="mb-5 bg-dark text-light p-4 rounded" style="max-width:400px; background:#232323 !important;">
        <div class="mb-3">
            <label class="form-label">Job Type Name*</label>
            <input type="text" name="NewJobType" class="form-control bg-dark text-light border-secondary" required placeholder="e.g., General Cleaning">
        </div>
        <button type="submit" name="add_jobtype" class="btn btn-success">Add Job Type</button>
    </form>
</div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        let title = 'Success!';
        let text = '';
        
        if (msg === 'added') text = 'Handyman successfully added.';
        if (msg === 'updated') text = 'Handyman successfully updated.';
        if (msg === 'deleted') text = 'Handyman successfully deleted.';
        if (msg === 'jobtype_added') text = 'New job type added successfully.';
        if (msg === 'error') { title = 'Error!'; text = 'An operation could not be completed.'; }

        if (text) {
            Swal.fire(title, text, (msg==='error'?'error':'success'));
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the handyman and their job assignment.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?delete=' + id;
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>