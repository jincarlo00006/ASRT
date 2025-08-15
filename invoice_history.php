<?php
require 'database/database.php';
session_start();
$db = new Database();

if (!isset($_SESSION['client_id'])) {
    die("You must be logged in to view invoice chat.");
}

$client_id = $_SESSION['client_id'];

// Fetch all invoices for this client, including paid, unpaid, kicked, etc.
$invoices = $db->getClientInvoiceHistory($client_id); // Should return ALL invoices
$invoice_ids = array_column($invoices, 'Invoice_ID');

// Separate invoices by status
$due_invoices = [];
$paid_invoices = [];
$kicked_invoices = [];
foreach ($invoices as $inv) {
    $status = strtolower($inv['Status']);
    if ($status === 'paid') {
        $paid_invoices[] = $inv;
    } elseif ($status === 'kicked') {
        $kicked_invoices[] = $inv;
    } else { // unpaid, due, overdue, etc.
        $due_invoices[] = $inv;
    }
}

// Select invoice for chat (GET or POST)
$selected_invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : (isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0);
if (!$selected_invoice_id && count($due_invoices)) {
    $selected_invoice_id = $due_invoices[0]['Invoice_ID'];
} elseif (!$selected_invoice_id && count($paid_invoices)) {
    $selected_invoice_id = $paid_invoices[0]['Invoice_ID'];
} elseif (!$selected_invoice_id && count($kicked_invoices)) {
    $selected_invoice_id = $kicked_invoices[0]['Invoice_ID'];
}

// Fetch selected invoice details
$invoice = null;
foreach ($invoices as $inv) {
    if ($inv['Invoice_ID'] == $selected_invoice_id) {
        $invoice = $inv;
        break;
    }
}

// Fetch chat messages using public method
$chat_messages = [];
if ($selected_invoice_id) {
    // Make sure this function fetches ALL messages, including 'system'
    $chat_messages = $db->getInvoiceChatMessagesForClient($selected_invoice_id);
}

// Check invoice status
$is_paid = (strtolower($invoice['Status'] ?? '') === 'paid');
$is_kicked = (strtolower($invoice['Status'] ?? '') === 'kicked');

// Handle sending a message (only if not paid and not kicked)
if (isset($_POST['send_message']) && $selected_invoice_id && !$is_paid && !$is_kicked) {
    $msg = trim($_POST['message_text'] ?? '');
    $image_path = null;

    // File upload
    if (!empty($_FILES['image_file']['name'])) {
        $upload_dir = 'uploads/invoice_chat/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['image_file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
            $image_path = $upload_dir . $file_name;
        }
    }

    // Use a public method to insert message!
    $result = $db->sendInvoiceChat($selected_invoice_id, 'client', $client_id, $msg, $image_path);
    if (!$result) {
        echo "<div style='color:red;'>Failed to send message. Please contact support.</div>";
        exit();
    }
    header("Location: invoice_history.php?invoice_id=$selected_invoice_id");
    exit();
}

// Optionally, show a system message in chat if kicked
$show_kicked_message_in_chat = $is_kicked;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #111; color: #eee; }
        .chat-container { background: #191919; border-radius: 10px; max-width: 800px; margin: 32px auto; padding: 32px; }
        .chat-warning {
            background: #fffae6;
            color: #856404;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            padding: 12px 18px;
            text-align: center;
            margin-bottom: 22px;
            font-size: 1rem;
        }
        .chat-msg { margin-bottom: 24px; }
        .chat-msg.client { text-align: right; }
        .chat-msg.admin { text-align: left; }
        .chat-msg.system { text-align: center; }
        .chat-msg .msg-bubble {
            display: inline-block; padding: 12px 20px; border-radius: 18px;
            max-width: 70%; font-size: 1.1rem;
        }
        .chat-msg.admin .msg-bubble { background: #0ef; color: #111; }
        .chat-msg.client .msg-bubble { background: #252525; color: #eee; border: 1px solid #333; }
        .chat-msg.system .msg-bubble { background: #ffe082; color: #856404; font-style: italic; border: 1px solid #ffe58f; }
        .chat-msg .sender { font-size: 0.9rem; opacity: 0.7; margin-top: 2px; }
        .chat-msg .chat-image { max-width: 220px; max-height: 180px; border-radius: 10px; margin-top: 10px; display: block; margin-left: auto; margin-right: auto;}
        .chat-meta { font-size: 0.96rem; color: #aaa; margin-bottom: 14px;}
        .msg-date { font-size: 0.8rem; color: #888; margin-left: 8px; }
        .chat-form textarea { resize: none; }
        .invoice-status-badge { font-size: 0.85em; vertical-align: middle; }
        @media (max-width: 900px) {
            .chat-container { padding: 10px; }
            .flex-wrap-mobile { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="chat-container">
        <!-- BEGIN: Chat Policy Notice -->
        <div class="chat-warning">
            <i class="fa fa-exclamation-circle"></i>
            <b>Notice:</b> All conversation here must be strictly about business, invoices, unit rental, payments, Request of the owner or related concerns, Thank you and God bless.
        </div>
        <!-- END: Chat Policy Notice -->

        <div class="chat-meta mb-3">
            <b>Unit:</b> <?= htmlspecialchars($invoice['SpaceName'] ?? '') ?>
        </div>
        <div class="row mb-4 flex-wrap-mobile">
            <div class="col-md-6 mb-2 mb-md-0">
                <form method="get">
                    <label for="invoice_id" class="form-label">Select Due Invoice:</label>
                    <select name="invoice_id" id="invoice_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                        <?php foreach ($due_invoices as $inv): ?>
                            <option value="<?= $inv['Invoice_ID'] ?>"
                                <?= $inv['Invoice_ID'] == ($selected_invoice_id ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inv['SpaceName']) ?> (<?= htmlspecialchars($inv['InvoiceDate']) ?>)
                                <?php
                                $status = strtolower($inv['Status']);
                                if ($status === 'paid') {
                                    echo ' [PAID]';
                                } elseif ($status === 'unpaid' || $status === 'due' || $status === 'overdue') {
                                    echo ' [DUE]';
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-md-6">
                <form method="get">
                    <label for="other_invoice_id" class="form-label">Paid &amp; Kicked Invoices:</label>
                    <select name="invoice_id" id="other_invoice_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                        <option value="">-- Select The New invoice--</option>
                        <?php foreach ($paid_invoices as $inv): ?>
                            <option value="<?= $inv['Invoice_ID'] ?>"
                                <?= $inv['Invoice_ID'] == ($selected_invoice_id ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inv['SpaceName']) ?> (<?= htmlspecialchars($inv['InvoiceDate']) ?>) [PAID]
                            </option>
                        <?php endforeach; ?>
                        <?php foreach ($kicked_invoices as $inv): ?>
                            <option value="<?= $inv['Invoice_ID'] ?>"
                                <?= $inv['Invoice_ID'] == ($selected_invoice_id ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inv['SpaceName']) ?> (<?= htmlspecialchars($inv['InvoiceDate']) ?>) [KICKED]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        <div>
            <?php foreach ($chat_messages as $msg): 
                $is_client = $msg['Sender_Type'] === 'client';
                $is_admin = $msg['Sender_Type'] === 'admin';
                $is_system = $msg['Sender_Type'] === 'system';
            ?>
                <div class="chat-msg <?= $is_client ? 'client' : ($is_admin ? 'admin' : 'system') ?>">
                    <div class="msg-bubble">
                        <?= nl2br(htmlspecialchars($msg['Message'])) ?>
                        <?php if (!empty($msg['Image_Path'])): ?>
                            <img src="<?= htmlspecialchars($msg['Image_Path']) ?>" class="chat-image mt-2" alt="chat photo">
                        <?php endif; ?>
                    </div>
                    <?php if(!$is_system): ?>
                        <div class="sender"><?= htmlspecialchars($msg['SenderName'] ?? '') ?> <span class="msg-date"><?= htmlspecialchars($msg['Created_At'] ?? '') ?></span></div>
                    <?php else: ?>
                        <div class="sender"><span class="msg-date"><?= htmlspecialchars($msg['Created_At'] ?? '') ?></span></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($show_kicked_message_in_chat): ?>
                <div class="chat-msg system">
                  <div class="msg-bubble">
                    This conversation is locked. Please rent a unit or chat the owner or admin for further assistance.
                  </div>
                  <div class="sender"><span class="msg-date"><?= date('Y-m-d H:i:s') ?></span></div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$is_paid && !$is_kicked): ?>
        <form method="post" enctype="multipart/form-data" class="chat-form mt-4">
            <div class="row g-2 align-items-end">
                <div class="col-9">
                    <textarea name="message_text" class="form-control" rows="2" placeholder="Type your message..." required></textarea>
                </div>
                <div class="col-2">
                    <input type="file" name="image_file" accept="image/*" class="form-control">
                </div>
                <div class="col-1">
                    <input type="hidden" name="invoice_id" value="<?= $selected_invoice_id ?>">
                    <button type="submit" name="send_message" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </form>
        <?php elseif ($is_kicked): ?>
        <div class="alert alert-danger mt-4">
            <i class="fa fa-lock"></i>
            This conversation is locked. Please rent a unit or chat the owner or admin for further assistance.
        </div>
        <?php else: ?>
        <div class="alert alert-warning mt-4">
            <i class="fa fa-lock"></i>
            This invoice is already <b>PAID</b>. You can no longer send messages for this rent period.
        </div>
        <?php endif; ?>
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>