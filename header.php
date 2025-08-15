<?php
// Use this robust session_start check at the very top.
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
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light px-lg-3 py-lg-2 shadow-sm sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold fs-3 h-font" href="index.php">
      ASRT Commercial
    </a>
    <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link me-2 <?= $current_page == 'index.php' ? 'active shadow border border-primary bg-white' : '' ?>" href="index.php">
            Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link me-2 text-primary fw-bold handyman-glow <?= $current_page == 'handyman_type.php' ? 'active shadow border border-primary bg-white' : '' ?>" href="handyman_type.php">
            <i class="fa-solid fa-helmet-safety me-1"></i> Handyman
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link me-2 text-success fw-bold <?= $current_page == 'invoice_history.php' ? 'active shadow border border-success bg-white' : '' ?>" href="invoice_history.php" style="position: relative;">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Invoice
            <?php if ($invoice_alert_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                <?= $invoice_alert_count ?>
                <span class="visually-hidden">unread invoice alerts</span>
              </span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link me-2 text-warning fw-bold maintenance-glow <?= $current_page == 'maintenance.php' ? 'active shadow border border-warning bg-white' : '' ?>" href="maintenance.php">
            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Maintenance
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link me-2 text-secondary fw-bold about-glow <?= $current_page == 'about.php' ? 'active shadow border border-secondary bg-white' : '' ?>" href="about.php">
            <i class="fa-solid fa-circle-info me-1"></i> About
          </a>
        </li>
      </ul>
      <div class="d-flex">
        <?php if (isset($_SESSION['C_username'])): ?>
          <span class="me-2">Welcome, <b><?= htmlspecialchars($_SESSION['C_username']) ?></b></span>
          <button type="button" class="btn btn-danger shadow-none" data-bs-toggle="modal" data-bs-target="#logoutModal">
            Logout
          </button>
        <?php else: ?>
          <button type="button" class="btn btn-outline-dark shadow-none me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
          <button type="button" class="btn btn-dark shadow-none" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<?php if (isset($_SESSION['C_username'])): ?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="logout.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">Are you sure?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p>Are you sure you want to logout?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Yes, Logout</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

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
                <i class="fa fa-eye"></i>
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
                    <i class="fa fa-eye"></i>
                  </span>
                </div>
                <div class="form-text text-muted">Must contain at least 1 uppercase and 1 special character.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                  <input type="password" class="form-control shadow-none" name="confirm_password" id="reg_confirm_password" required>
                  <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('reg_confirm_password', this)">
                    <i class="fa fa-eye"></i>
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

<!-- FontAwesome CDN for eye icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script>
function togglePassword(inputId, iconSpan) {
  var input = document.getElementById(inputId);
  var icon = iconSpan.querySelector('i');
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
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