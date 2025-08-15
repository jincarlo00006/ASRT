<?php
// Require your single database file. Adjust path if needed.
require 'database/database.php';
session_start();

$db = new Database();

$is_logged_in = isset($_SESSION['C_username']) && isset($_SESSION['client_id']);

$spaces = [];
$requests = [];
$message = '';

if ($is_logged_in) {
    $client_id = $_SESSION['client_id'];

    // Show success message from session (after redirect)
    if (isset($_SESSION['maintenance_success'])) {
        $message = "<div class='alert alert-success'>Request submitted successfully!</div>";
        unset($_SESSION['maintenance_success']);
    }

    // --- Handle Form Submission ---
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_request'])) {
        $space_id = intval($_POST['space_id']);

        if ($space_id) {
            // Check if there's already a pending request for this unit
            if ($db->hasPendingMaintenanceRequest($client_id, $space_id)) {
                $message = "<div class='alert alert-danger'>You already have a pending maintenance request for this unit. Please wait until it is completed.</div>";
            } else {
                // Create the new request using the transactional method
                if ($db->createMaintenanceRequest($client_id, $space_id)) {
                    $_SESSION['maintenance_success'] = true;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $message = "<div class='alert alert-danger'>Failed to submit request. Please try again.</div>";
                }
            }
        } else {
            $message = "<div class='alert alert-danger'>You must select a unit.</div>";
        }
    }

    // --- Fetch ALL Data for the Page ---
    $spaces = $db->getClientSpacesForMaintenance($client_id);
    $requests = $db->getClientMaintenanceHistory($client_id);

    // Get a simple array of space IDs that have pending requests for the dropdown
    $pending_space_ids = [];
    foreach ($requests as $req) {
        if (in_array($req['Status'], ['Submitted', 'In Progress'])) {
            $pending_space_ids[] = $req['Space_ID'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance</title>
    <?php require('links.php'); ?>
    <style>
        .pending-info { color: #c0392b; font-size: 0.95em; }
    </style>
</head>
<body>
<?php require('header.php'); ?>
<div class="container my-5">
    <?php if ($is_logged_in): ?>
        <h2 class="fw-bold text-center mb-4">Maintenance</h2>
        <?php if (!empty($message)) echo $message; ?>

        <?php if (!empty($spaces)): ?>
        <div class="card mb-5 mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">Submit a New Maintenance Request</div>
            <div class="card-body">
                <form method="post" id="maintenanceForm">
                    <div class="mb-3">
                        <label class="form-label">Select Your Unit</label>
                        <select name="space_id" id="space_id" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($spaces as $space): ?>
                                <?php $is_pending = in_array($space['Space_ID'], $pending_space_ids); ?>
                                <option value="<?= htmlspecialchars($space['Space_ID']) ?>" 
                                        <?= $is_pending ? 'data-pending="1"' : '' ?>>
                                    <?= htmlspecialchars($space['Name']) ?>
                                    <?= $is_pending ? ' (Pending Request)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pending-info mt-2" id="pendingInfo" style="display:none;">
                            You already have an active maintenance request for this unit.
                        </div>
                    </div>
                    <button type="submit" name="submit_request" class="btn btn-primary w-100" id="submitBtn">Submit Request</button>
                </form>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-warning text-center mb-5 mx-auto" style="max-width: 600px;">
                You currently do not have any rented units.
            </div>
        <?php endif; ?>

        <div class="card mx-auto" style="max-width: 900px;">
            <div class="card-header bg-secondary text-white">My Maintenance Requests</div>
            <div class="card-body p-0">
                <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered m-0">
                        <thead>
                            <tr>
                                <th>Date</th><th>Unit</th><th>Status</th><th>Last Status Date</th><th>Handyman Assigned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['RequestDate']) ?></td>
                                <td><?= htmlspecialchars($req['SpaceName']) ?></td>
                                <td><?= htmlspecialchars($req['Status']) ?></td>
                                <td><?= htmlspecialchars($req['LastStatusDate']) ?></td>
                                <td><?= $req['Handyman_fn'] ? htmlspecialchars($req['Handyman_fn'] . ' ' . $req['Handyman_ln']) : '<span class="text-muted">Not assigned</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4"><em>No maintenance requests found.</em></div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center fw-bold fs-5 mb-4">
            Please login first to view or request maintenance.<br>
            <!-- Login/Register buttons could be added here -->
        </div>
    <?php endif; ?>
</div>
<?php require('footer.php'); ?>

<!-- Auto-disable submit button for pending units -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('space_id');
    var submitBtn = document.getElementById('submitBtn');
    var pendingInfo = document.getElementById('pendingInfo');

    function checkPending() {
        var selected = select.options[select.selectedIndex];
        if (selected && selected.getAttribute('data-pending') === "1") {
            submitBtn.disabled = true;
            pendingInfo.style.display = 'block';
        } else {
            submitBtn.disabled = false;
            pendingInfo.style.display = 'none';
        }
    }
    select.addEventListener('change', checkPending);
    checkPending(); // Run on page load
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
