<?php
require 'database/database.php';
session_start();
$db = new Database();

if (!isset($_SESSION['client_id'])) {
    die("You must be logged in to view invoice chat.");
}

$client_id = $_SESSION['client_id'];

// Fetch all invoices for this client
$invoices = $db->getClientInvoiceHistory($client_id);
$invoice_ids = array_column($invoices, 'Invoice_ID');

// Select invoice for chat (GET or POST)
$selected_invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : (isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0);
if (!$selected_invoice_id && count($invoice_ids)) {
    $selected_invoice_id = $invoice_ids[0];
}

// --- Mark admin messages as seen for all invoices ---
foreach ($invoices as $inv) {
    $invoice_id = $inv['Invoice_ID'];
    // Get latest admin message id for this invoice
    $last_admin_msg = $db->runQuery(
        "SELECT MAX(Chat_ID) as maxid FROM invoice_chat WHERE Invoice_ID = ? AND Sender_Type = 'admin'",
        [$invoice_id]
    );
    $last_admin_msg_id = $last_admin_msg && $last_admin_msg['maxid'] ? $last_admin_msg['maxid'] : 0;

    // Save as last seen
    if ($last_admin_msg_id) {
        $db->executeStatement(
            "INSERT INTO invoice_chat_seen (Client_ID, Invoice_ID, LastSeenMsg_ID) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE LastSeenMsg_ID = VALUES(LastSeenMsg_ID)",
            [$client_id, $invoice_id, $last_admin_msg_id]
        );
    }
}

// Handle sending a message
if (isset($_POST['send_message']) && $selected_invoice_id) {
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
    $chat_messages = $db->getInvoiceChatMessagesForClient($selected_invoice_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .chat-msg .msg-bubble {
            display: inline-block; padding: 12px 20px; border-radius: 18px;
            max-width: 70%; font-size: 1.1rem;
        }
        .chat-msg.admin .msg-bubble { background: #0ef; color: #111; }
        .chat-msg.client .msg-bubble { background: #252525; color: #eee; border: 1px solid #333; }
        .chat-msg .sender { font-size: 0.9rem; opacity: 0.7; margin-top: 2px; }
        .chat-msg .chat-image { max-width: 220px; max-height: 180px; border-radius: 10px; margin-top: 10px; display: block; margin-left: auto; margin-right: auto;}
        .chat-meta { font-size: 0.96rem; color: #aaa; margin-bottom: 14px;}
        .msg-date { font-size: 0.8rem; color: #888; margin-left: 8px; }
        .chat-form textarea { resize: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="chat-container">
        <!-- BEGIN: Chat Policy Notice -->
        <div class="chat-warning">
            <i class="fa fa-exclamation-circle"></i>
            <b>Notice:</b> All conversation here must be strictly about business, invoices, unit rental, payments, or related concerns. Please do not use this chat for personal or unrelated matters.
        </div>
        <!-- END: Chat Policy Notice -->

        <div class="chat-meta mb-3">
            <b>Unit:</b> <?= htmlspecialchars($invoice['SpaceName'] ?? '') ?> |
            <b>Due:</b> <?= htmlspecialchars($invoice['InvoiceDate'] ?? '') ?> |
            <b>Status:</b> <?= htmlspecialchars($invoice['Status'] ?? '') ?>
        </div>
        <form method="get" class="mb-4">
            <label for="invoice_id" class="form-label">Select Invoice:</label>
            <select name="invoice_id" id="invoice_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                <?php foreach ($invoices as $inv): ?>
                    <option value="<?= $inv['Invoice_ID'] ?>" <?= $inv['Invoice_ID'] == $selected_invoice_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($inv['SpaceName']) ?> (<?= htmlspecialchars($inv['InvoiceDate']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <div>
            <?php foreach ($chat_messages as $msg): 
                $is_client = $msg['Sender_Type'] === 'client';
            ?>
                <div class="chat-msg <?= $is_client ? 'client' : 'admin' ?>">
                    <div class="msg-bubble">
                        <?= nl2br(htmlspecialchars($msg['Message'])) ?>
                        <?php if (!empty($msg['Image_Path'])): ?>
                            <img src="<?= htmlspecialchars($msg['Image_Path']) ?>" class="chat-image mt-2" alt="chat photo">
                        <?php endif; ?>
                    </div>
                    <div class="sender"><?= htmlspecialchars($msg['SenderName']) ?> <span class="msg-date"><?= htmlspecialchars($msg['Created_At']) ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>
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
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
        </div>
    </div>
</div>
<!-- FontAwesome for exclamation icon (optional, only if not already loaded) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</body>
</html>