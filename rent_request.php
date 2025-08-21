<?php
require 'database/database.php';
session_start();

$db = new Database();

$logged_in = isset($_SESSION['client_id']);
$client_id = $logged_in ? $_SESSION['client_id'] : null;

if (!$logged_in) {
    $show_login_modal = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $logged_in && isset($_POST['space_id'], $_POST['start_date'], $_POST['end_date'], $_POST['confirm_price'])) {
    $space_id = intval($_POST['space_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($db->createRentalRequest($client_id, $space_id, $start_date, $end_date)) {
        $success = "Your rental request has been sent to the admin!";
    }
}
$id = $_GET["space_id"];
 
// Use the classic robust function for available units
$available_units = $db->getAvailableUnitsForRental($id);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Request to Rent a Unit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
  <h2 class="fw-bold mb-4">Request to Rent a Unit</h2>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$logged_in): ?>
    <div class="alert alert-warning text-center">
      You must be logged in to request to rent a unit.<br>
      <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#loginModal" id="loginModalBtn">Login</button>
    </div>
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </div>
  <?php else: ?>
    <div class="row">
      <?php if ($available_units && count($available_units) > 0): ?>
        <?php foreach ($available_units as $space): ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($space['Name']) ?></h5>
                <p><strong>Type:</strong> <?= htmlspecialchars($space['SpaceTypeName']) ?></p>
                <p><strong>Price:</strong> ₱<?= number_format($space['Price'], 0) ?> per month</p>
                <form method="post" action="" onsubmit="return showConfirmModal(this, <?= htmlspecialchars(json_encode($space['Price'])) ?>);">
                  <input type="hidden" name="space_id" value="<?= htmlspecialchars($space['Space_ID']) ?>">
                  <input type="hidden" name="confirm_price" value="1">
                  <div class="mb-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required>
                  </div>
                  <div class="mb-2 text-muted small">
                    <em>Choose your preferred start and end date for your rental.</em>
                  </div>
                  <button type="submit" class="btn btn-success w-100">Request Rent</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">No available units to rent right now.</div>
        </div>
      <?php endif; ?>
    </div>
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </div>
  <?php endif; ?>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="negotiationModal" tabindex="-1" aria-labelledby="negotiationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="negotiationModalLabel">Are you sure?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="modalPriceText"></p>
        <p class="mb-2">Are you sure you want to request to rent at this price because the price is fixed</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="confirmRequestBtn">Yes, Request</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="login.php">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>
let pendingForm = null;
function showConfirmModal(form, price) {
  pendingForm = form;
  document.getElementById('modalPriceText').textContent = "Price: ₱" + Number(price).toLocaleString() + " per month";
  var modal = new bootstrap.Modal(document.getElementById('negotiationModal'));
  modal.show();
  return false;
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('confirmRequestBtn').onclick = function() {
    if (pendingForm) pendingForm.submit();
  };

  var loginModal = document.getElementById('loginModal');
  if (loginModal) {
    loginModal.addEventListener('shown.bs.modal', function () {
      var usernameInput = document.getElementById('username');
      if (usernameInput) usernameInput.focus();
    });
  }
});
</script>
<?php if (isset($show_login_modal) && $show_login_modal): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
  });
</script>
<?php endif; ?>
</body>
</html>