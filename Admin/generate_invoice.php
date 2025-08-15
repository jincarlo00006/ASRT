<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../database/database.php';
session_start();

$db = new Database();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die('Not authorized as admin.');
}

// --- Chat functionality ---
if (isset($_POST['send_message']) && isset($_POST['invoice_id'])) {
    $invoice_id = intval($_POST['invoice_id']);
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $message_text = trim($_POST['message_text'] ?? '');
    $image_path = null;

    if (!empty($_FILES['image_file']['name'])) {
        $upload_dir = '../uploads/invoice_chat/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['image_file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
            $image_path = 'uploads/invoice_chat/' . $file_name;
        }
    }

    $db->sendInvoiceChat($invoice_id, 'admin', $admin_id, $message_text, $image_path);
    header("Location: generate_invoice.php?chat_invoice_id=" . $invoice_id . "&status=" . ($_GET['status'] ?? 'new'));
    exit();
}

// --- Mark as Paid (send paid message in old invoice chat, create new invoice with chat continuity) ---
if (isset($_GET['toggle_status']) && isset($_GET['invoice_id'])) {
    $invoice_id = intval($_GET['invoice_id']);

    // Fetch the invoice to check status
    $invoice = $db->getSingleInvoiceForDisplay($invoice_id);
    if (!$invoice) {
        exit("Invoice not found.");
    }

    if (strtolower($invoice['Status']) === 'paid' || strtolower($invoice['Flow_Status']) === 'done') {
        // Already paid!
        header("Location: generate_invoice.php?chat_invoice_id=$invoice_id&status=" . ($_GET['status'] ?? 'new'));
        exit();
    }

    // 1. Mark invoice as paid in the database (updates Status and Flow_Status)
    $db->markInvoiceAsPaid($invoice_id);

    // 1b. Also mark latest rentalrequest as done for this invoice's client/unit
    $db->markRentalRequestDone($invoice['Client_ID'], $invoice['Space_ID']);

    // 2. Post PAID message in old chat (serves as a receipt)
    $paid_msg = "This rent has been PAID on " . date('Y-m-d') . ".";
    $db->sendInvoiceChat($invoice_id, 'system', null, $paid_msg, null);

    // 3. Create next invoice with correct period (next month after the most recent invoice's EndDate)
    $new_invoice_id = $db->createNextRecurringInvoiceWithChat($invoice_id);

    // 4. Optionally, add a system message in the NEW invoice chat
    // $db->sendInvoiceChat($new_invoice_id, 'system', null, "Previous rent was PAID on " . date('Y-m-d') . ".", null);

    // --- FIX: Only redirect if new invoice was actually created ---
    if ($new_invoice_id) {
        header("Location: generate_invoice.php?chat_invoice_id=$new_invoice_id&status=" . ($_GET['status'] ?? 'new'));
    } else {
        // fallback: stay in old invoice chat and show error (or just fallback)
        header("Location: generate_invoice.php?chat_invoice_id=$invoice_id&status=" . ($_GET['status'] ?? 'new') . "&error=recurring_invoice_failed");
    }
    exit();
}

// --- Display Logic ---
$invoices = [];
$show_chat = false;
$chat_invoice_id = null;
$chat_messages = [];
$invoice = null; // <-- fix: always define $invoice

// --- Invoice filter logic ---
$allowed_statuses = ['new', 'done', 'all'];
$status_filter = $_GET['status'] ?? 'new';
if (!in_array($status_filter, $allowed_statuses)) $status_filter = 'new';

if (!$show_chat) {
    if ($status_filter === 'all') {
        $invoices = array_merge(
            $db->getInvoicesByFlowStatus('new'),
            $db->getInvoicesByFlowStatus('done')
        );
    } else {
        $invoices = $db->getInvoicesByFlowStatus($status_filter);
    }
}

if (isset($_GET['chat_invoice_id'])) {
    $show_chat = true;
    $chat_invoice_id = intval($_GET['chat_invoice_id']);
}
if ($show_chat && $chat_invoice_id) {
    $invoice = $db->getSingleInvoiceForDisplay($chat_invoice_id);
    $chat_messages = $db->getInvoiceChatMessagesForClient($chat_invoice_id);
}

// Helper for countdown (output JS or static string)
function renderCountdown($due_date) {
    $due = strtotime($due_date);
    $now = time();
    $diff = $due - $now;
    if ($diff <= 0) {
        return '<span class="badge badge-overdue">OVERDUE</span>';
    }
    $id = 'countdown_' . uniqid();
    return '<span id="'.$id.'" class="badge badge-soon" data-duedate="'.$due_date.'"></span>
<script>
(function(){
    function updateCountdown_'.$id.'() {
        var due = new Date("'.$due_date.'T23:59:59").getTime();
        var now = new Date().getTime();
        var diff = due - now;
        var el = document.getElementById("'.$id.'");
        if (!el) return;
        if (diff <= 0) {
            el.textContent = "OVERDUE";
            el.className = "badge badge-overdue";
            return;
        }
        var d = Math.floor(diff / (1000*60*60*24));
        var h = Math.floor((diff%(1000*60*60*24))/(1000*60*60));
        var m = Math.floor((diff%(1000*60*60))/(1000*60));
        var s = Math.floor((diff%(1000*60))/1000);
        el.textContent = "Due in " + (d>0?d + "d ":"") + (h>0?h + "h ":"") + (m>0?m + "m ":"") + s + "s";
        setTimeout(updateCountdown_'.$id.', 1000);
    }
    updateCountdown_'.$id.'();
})();
</script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .badge { font-size: 0.9rem; }
        .card { background-color: #1e1e1e; border: 1px solid #333; }
        .table thead { background-color: #333; }
        .badge-overdue { background-color: #d9534f !important; }
        .badge-unpaid { background-color: #f0ad4e !important; color: #222; }
        .badge-soon { background-color: #17a2b8 !important; color: #fff; }
        .badge-flow-done { background: #22bb33 !important; }
        .badge-flow-new { background: #0ef !important; color: #111; }
        .chat-container { background: #191919; border-radius: 10px; max-width: 800px; margin: 0 auto 24px auto; padding: 32px 24px 24px 24px;}
        .chat-msg { margin-bottom: 24px; }
        .chat-msg.admin { text-align: right; }
        .chat-msg.system { text-align: center; }
        .chat-msg.system .msg-bubble { background: #ffe082; color: #856404; font-style: italic; border: 1px solid #ffe58f; }
        .chat-msg .msg-bubble {
            display: inline-block; padding: 12px 20px; border-radius: 18px;
            max-width: 70%; font-size: 1.1rem;
        }
        .chat-msg.admin .msg-bubble { background: #0ef; color: #111; }
        .chat-msg.client .msg-bubble { background: #252525; color: #eee; border: 1px solid #333; }
        .chat-msg .sender { font-size: 0.9rem; opacity: 0.7; margin-top: 2px; }
        .chat-msg .chat-image { max-width: 220px; max-height: 180px; border-radius: 10px; margin-top: 10px; display: block; margin-left: auto; margin-right: auto;}
        .chat-meta { font-size: 0.9rem; color: #aaa; margin-bottom: 14px;}
        .msg-date { font-size: 0.8rem; color: #888; margin-left: 8px; }
        .chat-form input[type="file"] { color: #0ef; }
        .chat-form textarea { resize: none; }
        .chat-actions { margin-top: 16px; text-align: center; }
        .btn-filter { margin-right: 6px; }
        .btn-filter.active { outline: 2px solid #fff; }
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
    <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice-dollar me-2"></i>Invoice Chat</h2>
        <div>
            <a href="?status=new" class="btn btn-info btn-filter<?= $status_filter === 'new' ? ' active' : '' ?>">Show New</a>
            <a href="?status=done" class="btn btn-success btn-filter<?= $status_filter === 'done' ? ' active' : '' ?>">Show Done</a>
            <a href="?status=all" class="btn btn-secondary btn-filter<?= $status_filter === 'all' ? ' active' : '' ?>">Show All</a>
            <a href="dashboard.php" class="btn btn-outline-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="alert alert-dark border-light shadow">
        Mark an invoice as paid ➡️ confirm. The system will send a chat message as a receipt for the paid date and create a new invoice with chat continuity for the next rental period.
    </div>

    <?php if ($show_chat && $invoice): ?>
        <div class="chat-container mb-4">
            <div class="chat-meta mb-3">
                <b><?= htmlspecialchars($invoice['Client_fn'] ?? '') . ' ' . htmlspecialchars($invoice['Client_ln'] ?? '') ?></b>
                | Issued: <b><?= htmlspecialchars($invoice['InvoiceDate'] ?? '') ?></b>
                | Due: <b><?= htmlspecialchars($invoice['EndDate'] ?? $invoice['InvoiceDate'] ?? '') ?></b>
                <?= isset($invoice['EndDate']) ? renderCountdown($invoice['EndDate']) : '' ?>
                | Unit: <b><?= htmlspecialchars($invoice['UnitName'] ?? '') ?></b>
            </div>
            <div>
                <?php foreach ($chat_messages as $msg):
                    $is_admin = $msg['Sender_Type'] === 'admin';
                    $is_system = $msg['Sender_Type'] === 'system';
                    $is_client = $msg['Sender_Type'] === 'client';
                ?>
                    <div class="chat-msg <?= $is_admin ? 'admin' : ($is_system ? 'system' : ($is_client ? 'client' : '')) ?>">
                        <div class="msg-bubble">
                            <?= nl2br(htmlspecialchars($msg['Message'])) ?>
                            <?php if (!empty($msg['Image_Path'])): ?>
                                <img src="../<?= htmlspecialchars($msg['Image_Path']) ?>" class="chat-image mt-2" alt="chat photo">
                            <?php endif; ?>
                        </div>
                        <div class="sender">
                            <?= htmlspecialchars($msg['SenderName'] ?? ($is_system ? 'System' : ($is_admin ? 'Admin' : 'Client'))) ?> 
                            <span class="msg-date"><?= htmlspecialchars($msg['Created_At'] ?? '') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" enctype="multipart/form-data" class="chat-form mt-4">
                <div class="row g-2 align-items-end">
                    <div class="col-9">
                        <textarea name="message_text" class="form-control" rows="2" placeholder="Type your message..."></textarea>
                    </div>
                    <div class="col-2">
                        <input type="file" name="image_file" accept="image/*" class="form-control">
                    </div>
                    <div class="col-1">
                        <input type="hidden" name="invoice_id" value="<?= $chat_invoice_id ?>">
                        <button type="submit" name="send_message" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </form>
            <div class="chat-actions">
                <button 
                    class="btn btn-success"
                    onclick="confirmPaid(this)"
                    data-href="generate_invoice.php?toggle_status=paid&invoice_id=<?= $invoice['Invoice_ID'] ?>&status=<?= htmlspecialchars($status_filter) ?>">
                    <i class="fas fa-check-circle"></i> Mark as Paid
                </button>
            </div>
            <div class="mt-3">
                <a href="generate_invoice.php?status=<?= htmlspecialchars($status_filter) ?>" class="btn btn-secondary btn-sm">&larr; Back to Invoices</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$show_chat): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center align-middle table-dark">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Unit</th>
                    <th>Due</th>
                    <th>Chat</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ctr = 1;
            foreach ($invoices as $row):
            ?>
                <tr>
                    <td><?= $ctr++ ?></td>
                    <td class="text-start"><?= htmlspecialchars(($row['Client_fn'] ?? '') . ' ' . ($row['Client_ln'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($row['UnitName'] ?? '') ?></td>
                    <td>
                        <?= isset($row['EndDate']) ? renderCountdown($row['EndDate']) : '<span class="text-muted">N/A</span>' ?>
                    </td>
                    <td>
                        <a href="generate_invoice.php?chat_invoice_id=<?= $row['Invoice_ID'] ?>&status=<?= htmlspecialchars($status_filter) ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-comments"></i> Chat
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="5" class="text-center text-muted">No invoices available for this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    </div>
</div>
<script>
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content').classList.toggle('collapsed');
    });
    function confirmPaid(button) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Mark this invoice as PAID? The system will send a chat message as a receipt and create the next invoice for the next rental period with chat continuity.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0ef',
            cancelButtonColor: '#888',
            confirmButtonText: 'Yes, mark it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = button.getAttribute('data-href');
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>