<?php
// Use this robust session_start check at the very top.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database/database.php';
$db = new Database();

// --- Invoice Notification Badge Logic (including unseen admin messages) ---
$new_admin_msg_count = 0;
if (isset($_SESSION['client_id'])) {
    $invoice_list = $db->getClientInvoiceHistory($_SESSION['client_id']);
    foreach ($invoice_list as $inv) {
        $invoice_id = $inv['Invoice_ID'];

        // Get last seen admin message id for this invoice
        $seen_row = $db->runQuery("SELECT LastSeenMsg_ID FROM invoice_chat_seen WHERE Client_ID = ? AND Invoice_ID = ?", [$_SESSION['client_id'], $invoice_id]);
        $last_seen_id = $seen_row ? $seen_row['LastSeenMsg_ID'] : 0;

        // Count admin messages with id > last_seen_id
        $new_msgs = $db->runQuery(
            "SELECT COUNT(*) as cnt FROM invoice_chat WHERE Invoice_ID = ? AND Sender_Type = 'admin' AND Chat_ID > ?", 
            [$invoice_id, $last_seen_id]
        );
        $new_admin_msg_count += ($new_msgs ? (int)$new_msgs['cnt'] : 0);
    }
}

// Flash message logic (unchanged)
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
// ... (flash message session logic unchanged) ...

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
        <li class="nav-item" style="position: relative;">
          <a class="nav-link me-2 text-success fw-bold <?= $current_page == 'invoice_history.php' ? 'active shadow border border-success bg-white' : '' ?>" href="invoice_history.php">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Invoice
            <?php if ($new_admin_msg_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                <i class="fa fa-envelope"></i>
                <span class="visually-hidden">unread admin messages</span>
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
<!-- ... rest of your modals and JS ... -->