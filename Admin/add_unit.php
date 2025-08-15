<?php
session_start();
require_once '../database/database.php';

// Turn on error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new Database();

// --- Admin auth check ---
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}
$ua_id = $_SESSION['admin_id'] ?? null;

$success_unit = '';
$error_unit = '';
$success_type = '';
$error_type = '';

// --- Handle photo delete ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'delete_photo') {
    $space_id = intval($_POST['space_id'] ?? 0);
    if ($space_id) {
        $space = $db->getSpacePhoto($space_id);
        if ($space && !empty($space['Photo'])) {
            $filepath = __DIR__ . "/../uploads/unit_photos/" . $space['Photo'];
            if (file_exists($filepath)) unlink($filepath);
        }
        $db->removeSpacePhoto($space_id);
        $success_unit = "Photo deleted successfully!";
    }
}

// --- Handle photo update/upload ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'update_photo') {
    $space_id = intval($_POST['space_id'] ?? 0);
    if ($space_id && isset($_FILES['new_photo']) && $_FILES['new_photo']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file = $_FILES['new_photo'];
        if (!in_array($file['type'], $allowed_types)) {
            $error_unit = "Invalid file type for photo.";
        } elseif ($file['size'] > 2*1024*1024) {
            $error_unit = "Photo is too large (max 2MB).";
        } else {
            // Delete old photo if exists
            $space = $db->getSpacePhoto($space_id);
            if ($space && !empty($space['Photo'])) {
                $filepath = __DIR__ . "/../uploads/unit_photos/" . $space['Photo'];
                if (file_exists($filepath)) unlink($filepath);
            }
            // Save new
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = "adminunit_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $upload_dir = __DIR__ . "/../uploads/unit_photos/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $db->updateSpacePhoto($space_id, $filename);
                $success_unit = "Photo updated successfully!";
            } else {
                $error_unit = "Failed to upload new photo.";
            }
        }
    }
}

// --- Handle form submission for new space/unit ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'unit') {
    $name = trim($_POST['name'] ?? '');
    $spacetype_id = intval($_POST['spacetype_id'] ?? 0);
    $price = isset($_POST['price']) && is_numeric($_POST['price']) ? floatval($_POST['price']) : null;

    // Handle file upload (only one photo)
    $photo_filename = null;
    $upload_dir = __DIR__ . "/../uploads/unit_photos/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $error_unit = "Invalid file type for photo.";
        } elseif ($file['size'] > 2*1024*1024) {
            $error_unit = "Photo is too large (max 2MB).";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = "adminunit_" . time() . "." . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $photo_filename = $filename;
            } else {
                $error_unit = "Failed to upload photo.";
            }
        }
    }

    if (empty($name) || empty($spacetype_id) || $price === null || empty($ua_id)) {
        $error_unit = "Please fill in all required fields.";
    } elseif ($price < 0) {
        $error_unit = "Price must be a non-negative number.";
    } elseif ($db->isSpaceNameExists($name)) {
        $error_unit = "A space/unit with this name already exists.";
    } elseif (!$error_unit) {
        if ($db->addNewSpace($name, $spacetype_id, $ua_id, $price, $photo_filename)) {
            $success_unit = "Space/unit added successfully!";
        } else {
            $error_unit = "A database error occurred. The unit could not be added.";
        }
    }
}

// --- Handle form submission for new space type ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'type') {
    $spacetype_name = trim($_POST['spacetype_name'] ?? '');

    if (empty($spacetype_name)) {
        $error_type = "Please enter a space type name.";
    } else {
        $existing_types = $db->getAllSpaceTypes();
        $existing = array_filter($existing_types, function($type) use ($spacetype_name) {
            return strtolower(trim($type['SpaceTypeName'])) === strtolower(trim($spacetype_name));
        });
        if ($existing) {
            $error_type = "This space type already exists.";
        } else {
            if ($db->addSpaceType($spacetype_name)) {
                $success_type = "Space type added successfully!";
            } else {
                $error_type = "A database error occurred. Space type could not be added.";
            }
        }
    }
}

// --- Fetch Data for Display ---
$spacetypes = $db->getAllSpaceTypes();
$spaces = $db->getAllSpacesWithDetails();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Add Space/Unit & Space Type</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
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
  td img { max-width: 80px; max-height: 80px; }
  .unit-photo-form input[type="file"] { width: 120px; display: inline; }
  .unit-photo-form { display: inline; }
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
<div class="container py-5">
  <div class="row g-5">
    <!-- Add New Space/Unit -->
    <div class="col-lg-6">
      <div class="card p-4 mb-5 bg-dark text-light">
        <h2 class="mb-4 text-center">Add New Space/Unit</h2>

        <?php if ($success_unit): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success_unit) ?></div>
        <?php elseif ($error_unit): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error_unit) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="row g-3" autocomplete="off" style="background:#232323; border-radius:12px; padding:20px;">
          <input type="hidden" name="form_type" value="unit" />
          <div class="col-12">
            <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
            <input id="name" type="text" class="form-control form-control-lg bg-dark text-light border-secondary" name="name" placeholder="Unit name" required />
          </div>
          <div class="col-12">
            <label for="spacetype_id" class="form-label fw-semibold">Space Type <span class="text-danger">*</span></label>
            <select id="spacetype_id" name="spacetype_id" class="form-select form-select-lg bg-dark text-light border-secondary" required>
              <option value="" selected disabled>Select Type</option>
              <?php foreach ($spacetypes as $stype): ?>
                <option value="<?= $stype['SpaceType_ID'] ?>"><?= htmlspecialchars($stype['SpaceTypeName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label for="price" class="form-label fw-semibold">Price (PHP) <span class="text-danger">*</span></label>
            <input id="price" type="number" step="100" min="0" class="form-control form-control-lg bg-dark text-light border-secondary" name="price" placeholder="0.00" required />
            <small id="priceDisplay" class="text-muted"></small>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Photo (max 2MB, JPG/PNG/GIF):</label>
            <input type="file" name="photo" accept="image/*" class="form-control bg-dark text-light border-secondary" required />
          </div>
          <div class="col-12 text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5">Add Space/Unit</button>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-3 px-4">Back to Dashboard</a>
          </div>
        </form>
      </div>
      <div class="card p-4 bg-dark text-light">
        <h3 class="mb-4 text-center">Existing Spaces/Units</h3>
        <?php if (empty($spaces)): ?>
          <div class="alert alert-info text-center">No spaces/units found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle text-center mb-0 table-dark">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Price (PHP)</th>
                  <th>Photo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($spaces as $space): ?>
                  <tr>
                    <td><?= $space['Space_ID'] ?></td>
                    <td><?= htmlspecialchars($space['Name']) ?></td>
                    <td><?= htmlspecialchars($space['SpaceTypeName']) ?></td>
                    <td><?= number_format($space['Price'], 2) ?></td>
                    <td>
                      <?php if (!empty($space['Photo'])): ?>
                        <img src="../uploads/unit_photos/<?= htmlspecialchars($space['Photo']) ?>" alt="Photo">
                        <form method="post" class="unit-photo-form" onsubmit="return confirm('Delete this photo?');" style="display:inline">
                          <input type="hidden" name="form_type" value="delete_photo">
                          <input type="hidden" name="space_id" value="<?= $space['Space_ID'] ?>">
                          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                        <form method="post" enctype="multipart/form-data" class="unit-photo-form" style="display:inline">
                          <input type="file" name="new_photo" accept="image/*" required>
                          <input type="hidden" name="form_type" value="update_photo">
                          <input type="hidden" name="space_id" value="<?= $space['Space_ID'] ?>">
                          <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </form>
                      <?php else: ?>
                        <span>No photo</span>
                        <form method="post" enctype="multipart/form-data" class="unit-photo-form" style="display:inline">
                          <input type="file" name="new_photo" accept="image/*" required>
                          <input type="hidden" name="form_type" value="update_photo">
                          <input type="hidden" name="space_id" value="<?= $space['Space_ID'] ?>">
                          <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add New Space Type -->
    <div class="col-lg-6">
      <div class="card p-4 mb-5 bg-dark text-light">
        <h2 class="mb-4 text-center">Add New Space Type</h2>
        <?php if ($success_type): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success_type) ?></div>
        <?php elseif ($error_type): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error_type) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3" autocomplete="off" style="background:#232323; border-radius:12px; padding:20px;">
          <input type="hidden" name="form_type" value="type" />
          <div class="col-12">
            <label for="spacetype_name" class="form-label fw-semibold">Space Type Name <span class="text-danger">*</span></label>
            <input id="spacetype_name" type="text" class="form-control form-control-lg bg-dark text-light border-secondary" name="spacetype_name" placeholder="e.g. Apartment, Studio, Commercial" required />
          </div>
          <div class="col-12 text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5">Add Space Type</button>
          </div>
        </form>
      </div>
      <div class="card p-4 bg-dark text-light">
        <h3 class="mb-4 text-center">Existing Space Types</h3>
        <?php if (empty($spacetypes)): ?>
          <div class="alert alert-info text-center">No space types found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle text-center mb-0 table-dark">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($spacetypes as $type): ?>
                  <tr>
                    <td><?= $type['SpaceType_ID'] ?></td>
                    <td><?= htmlspecialchars($type['SpaceTypeName']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
    document.getElementById('price').addEventListener('input', function() {
      var val = this.value;
      if (val !== "" && !isNaN(val)) {
        document.getElementById('priceDisplay').textContent = "₱ " + Number(val).toLocaleString();
      } else {
        document.getElementById('priceDisplay').textContent = "";
      }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>