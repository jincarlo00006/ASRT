<?php
// Robust session_start check at the very top.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database/database.php';
$db = new Database();

// --- Invoice Notification Badge Logic ---
$invoice_alert_count = 0;
if (isset($_SESSION['client_id'])) {
    $invoice_list = $db->getClientInvoiceHistory($_SESSION['client_id']);
    foreach ($invoice_list as $inv) {
        $due = isset($inv['InvoiceDate']) ? $inv['InvoiceDate'] : '';
        $is_unpaid = ($inv['Status'] === 'unpaid');
        $is_overdue = ($due && $inv['Status'] === 'unpaid' && strtotime($due) < strtotime(date('Y-m-d')));
        if ($is_unpaid || $is_overdue) {
            $invoice_alert_count++;
        }
    }
}

function display_flash_message($icon, $title, $message) {
    $safe_message = addslashes($message);
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ 
                    icon: '{$icon}', 
                    title: '{$title}', 
                    text: '{$safe_message}' 
                });
            });
          </script>";
}

if (isset($_SESSION['login_error'])) {
    display_flash_message('error', 'Login Failed', $_SESSION['login_error']);
    unset($_SESSION['login_error']);
}
if (isset($_SESSION['logout_success'])) {
    display_flash_message('success', 'Logged Out', $_SESSION['logout_success']);
    unset($_SESSION['logout_success']);
}
if (isset($_SESSION['register_success'])) {
    display_flash_message('success', 'Registration Successful', $_SESSION['register_success']);
    unset($_SESSION['register_success']);
}
if (isset($_SESSION['register_error'])) {
    display_flash_message('error', 'Registration Failed', $_SESSION['register_error']);
    unset($_SESSION['register_error']);
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['client_id']);
?>

<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<nav class="navbar navbar-expand-lg shadow-sm" style="background:#fff !important; min-height:52px;">
  <div class="container-fluid px-lg-5 px-3">
    <!-- Brand/title always left -->
    <a class="navbar-brand h-100" href="index.php" id="navbarBrand"
       style="font-size:2rem; display:flex; align-items:center; height:52px;">
      <span class="me-2">
        <i class="bi bi-house-door-fill"></i>
      </span>
      <span class="brand-bold">ASRT COMMERCIAL</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Navigation always right -->
    <div class="collapse navbar-collapse justify-content-end align-items-stretch" id="navbarNav">
      <ul class="navbar-nav align-items-stretch d-flex">
        <li class="nav-item h-100">
          <a class="nav-link h-100 d-flex align-items-center <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item h-100">
          <a class="nav-link h-100 d-flex align-items-center <?= $current_page == 'invoice_history.php' ? 'active' : '' ?>" href="invoice_history.php" style="position: relative;">
            Payment
            <?php if ($invoice_alert_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                <?= $invoice_alert_count ?>
                <span class="visually-hidden">unread invoice alerts</span>
              </span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item h-100">
          <a class="nav-link h-100 d-flex align-items-center <?= $current_page == 'handyman_type.php' ? 'active' : '' ?>" href="handyman_type.php">Handyman</a>
        </li>
        <li class="nav-item h-100">
          <a class="nav-link h-100 d-flex align-items-center <?= $current_page == 'maintenance.php' ? 'active' : '' ?>" href="maintenance.php">Maintenance</a>
        </li>
        <li class="nav-item h-100">
          <a class="nav-link h-100 d-flex align-items-center <?= $current_page == 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
        </li>
        <?php if ($is_logged_in): ?>
          <?php if ($current_page != 'dashboard.php'): ?>
          <li class="nav-item h-100 d-flex align-items-center">
            <a href="dashboard.php" class="btn btn-primary px-3 mx-1 h-100 d-flex align-items-center justify-content-center navbar-btn-equal responsive-btn-margin">Back to Dashboard</a>
          </li>
          <?php endif; ?>
          <li class="nav-item h-100 d-flex align-items-center">
            <form action="logout.php" method="post" class="d-inline h-100">
              <button type="submit" class="btn btn-danger px-3 mx-1 h-100 d-flex align-items-center justify-content-center navbar-btn-equal responsive-btn-margin">Logout</button>
            </form>
          </li>
        <?php else: ?>
          <li class="nav-item h-100 d-flex align-items-center">
            <button type="button" class="btn btn-outline-dark px-3 mx-1 h-100 d-flex align-items-center justify-content-center navbar-btn-equal responsive-btn-margin" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
          </li>
          <li class="nav-item h-100 d-flex align-items-center">
            <button type="button" class="btn btn-dark px-3 mx-1 h-100 d-flex align-items-center justify-content-center navbar-btn-equal responsive-btn-margin" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="login.php">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center">
            <i class="bi bi-person-circle fs-3 me-2"></i> Client Login
          </h5>
          <button type="reset" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control shadow-none" name="username" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control shadow-none" name="password" id="login_password" required>
              <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('login_password', this)">
                <i class="bi bi-eye"></i>
              </span>
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-2">
            <button type="submit" class="btn btn-dark shadow-none">LOGIN</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="register.php" onsubmit="return checkRegisterForm();">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center">
            <i class="bi bi-person-lines-fill fs-3 me-2"></i> Client Register
          </h5>
          <button type="reset" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control shadow-none" name="fname" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control shadow-none" name="lname" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control shadow-none" name="email" id="reg_email" required>
                <span id="email_msg" class="text-danger small"></span>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control shadow-none" name="phone" id="reg_phone" maxlength="11" pattern="\d{11}" inputmode="numeric" required>
                <div class="form-text text-muted">Must be 11 digits (numbers only, e.g., 09XXXXXXXXX)</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control shadow-none" name="username" id="reg_username" required>
                <span id="username_msg" class="text-danger small"></span>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control shadow-none" name="password" id="reg_password" required>
                  <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('reg_password', this)">
                    <i class="bi bi-eye"></i>
                  </span>
                </div>
                <div class="form-text text-muted">Must contain at least 1 uppercase and 1 special character.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" class="form-control shadow-none" name="confirm_password" id="reg_confirm_password" required>
                  <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('reg_confirm_password', this)">
                    <i class="bi bi-eye"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="text-center my-1">
            <button type="submit" class="btn btn-dark shadow-none custom-bg">REGISTER</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function togglePassword(inputId, iconSpan) {
  var input = document.getElementById(inputId);
  var icon = iconSpan.querySelector('i');
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Registration validation for email
  const reg_email = document.getElementById('reg_email');
  if (reg_email) {
    reg_email.addEventListener('blur', function() {
      var email = this.value;
      if (email.length > 0) {
        fetch('ajax/check_user.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
          document.getElementById('email_msg').textContent = data.exists ? data.message : '';
        });
      }
    });
  }

  // Registration validation for username
  const reg_username = document.getElementById('reg_username');
  if (reg_username) {
    reg_username.addEventListener('blur', function() {
      var username = this.value;
      if (username.length > 0) {
        fetch('ajax/check_user.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'username=' + encodeURIComponent(username)
        })
        .then(response => response.json())
        .then(data => {
          document.getElementById('username_msg').textContent = data.exists ? data.message : '';
        });
      }
    });
  }

  // Phone number input validation for 11 digits and numbers only
  var phoneInput = document.getElementById('reg_phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });
  }
});

// This function prevents form submission if the server-side validation has found an error.
function checkRegisterForm() {
  var emailMsg = document.getElementById('email_msg').textContent;
  var usernameMsg = document.getElementById('username_msg').textContent;
  var phoneInput = document.getElementById('reg_phone');
  if(emailMsg || usernameMsg) {
    return false;
  }
  // Additional client-side check: must be 11 digits
  if(phoneInput && phoneInput.value.length !== 11) {
    alert("Phone number must be exactly 11 digits.");
    phoneInput.focus();
    return false;
  }
  return true;
}
</script>

<style>
.navbar, .navbar * {
  font-family: 'Poppins', sans-serif !important;
  font-weight: 500 !important;
}

/* Consistent icon style for navbar brand */
.navbar-brand .bi {
  font-size: 2rem !important;
  color: #2563eb !important;
  vertical-align: middle;
  display: inline-block;
}

/* Bold only the brand text */
.brand-bold {
  font-weight: 700 !important;
  letter-spacing: 0.01em;
}

/* Other navbar styles unchanged */
.nav-link, .btn, .navbar-btn-equal {
  height: 52px !important;
  min-height: 52px !important;
  display: flex;
  align-items: center;
  padding: 0 18px !important;
  box-sizing: border-box;
}
.navbar-brand {
  height: 52px !important;
  min-height: 52px !important;
  display: flex;
  align-items: center;
  font-size: 2rem;
  transition: font-size 0.25s cubic-bezier(.4,0,.2,1);
}
.nav-link {
  color: #1a2946 !important;
  letter-spacing: 0.02em;
  font-size: 1.15rem;
  border-bottom: none !important;
  background: none !important;
  transition: color 0.2s cubic-bezier(.4,0,.2,1), border-bottom 0.2s cubic-bezier(.4,0,.2,1);
}
.nav-link.active, .nav-link:hover {
  color: #2563eb !important;
  border-bottom: 3px solid #2563eb;
  background: none !important;
}
.nav-link[href="index.php"]:hover,
.nav-link[href="index.php"].active {
  border-bottom: none !important;
  color: #2563eb !important;
  background: none !important;
}
.btn-danger {
  background: #e11d48 !important;
  border: none;
  border-radius: 7px;
  transition: background 0.2s cubic-bezier(.4,0,.2,1);
  font-size: 1rem;
  height: 52px !important;
  min-height: 52px !important;
  display: flex;
  align-items: center;
}
.btn-primary {
  background: #2563eb !important;
  border: none;
  border-radius: 7px;
  color: #fff !important;
  font-size: 1rem;
  transition: background 0.2s cubic-bezier(.4,0,.2,1), color 0.2s cubic-bezier(.4,0,.2,1);
  height: 52px !important;
  min-height: 52px !important;
  display: flex;
  align-items: center;
}
.btn-primary:hover {
  background: #1742b4 !important;
  color: #fff !important;
}
/* Responsive adjustments for title and nav height */
@media (max-width: 991.98px) {
  .navbar-brand {
    font-size: 1.3rem !important;
    height: 44px !important;
    min-height: 44px !important;
  }
  .navbar-brand .bi {
    font-size: 1.3rem !important;
  }
  .nav-link, .btn, .navbar-btn-equal {
    height: 44px !important;
    min-height: 44px !important;
    padding: 10px 18px !important;
    font-size: 1rem !important;
  }
  .responsive-btn-margin {
    margin-right: 8px !important;
  }
}
@media (max-width: 576px) {
  .navbar-brand {
    font-size: 1.05rem !important;
    height: 38px !important;
    min-height: 38px !important;
  }
  .navbar-brand .bi {
    font-size: 1.05rem !important;
  }
  .nav-link, .btn, .navbar-btn-equal {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.98rem !important;
    padding: 8px 10px !important;
  }
  .container-fluid {
    padding-left: 8px !important;
    padding-right: 8px !important;
  }
  .responsive-btn-margin {
    margin-right: 12px !important;
  }
}
</style>