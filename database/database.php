<?php

class Database {

    private $host = "localhost";
    private $dbname = "asrp"; // UPDATED DATABASE NAME
    private $user = "root";
    private $pass = "";
    private $pdo;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // --- Private Helper Methods ---
    // CHANGED FROM private TO protected FOR EXTENSIBILITY, BUT NOT public!
    protected function runQuery($sql, $params = [], $fetchAll = false) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
        } catch (PDOException $e) { return false; }
    }

public function executeStatement($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) { return false; }
    }

    protected function insertAndGetId($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) { return false; }
    }

    public function invoiceExists($client_id, $due_date) {
        $sql = "SELECT 1 FROM invoice WHERE Client_ID = ? AND InvoiceDate = ?";
        $result = $this->runQuery($sql, [$client_id, $due_date]);
        return (bool)$result;
    }


    public function getUnitPhotosForClient($client_id) {
    $sql = "SELECT Space_ID, BusinessPhoto1, BusinessPhoto2, BusinessPhoto3, BusinessPhoto4, BusinessPhoto5 
            FROM clientspace 
            WHERE Client_ID = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$client_id]);
    $photos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $photos[$row['Space_ID']] = array_values(array_filter([
            $row['BusinessPhoto1'],
            $row['BusinessPhoto2'],
            $row['BusinessPhoto3'],
            $row['BusinessPhoto4'],
            $row['BusinessPhoto5'],
        ]));
    }
    return $photos;
}

public function getAllUnitPhotosForUnits($unit_ids) {
    if (empty($unit_ids)) return [];
    // Prepare placeholders for array of unit IDs
    $placeholders = implode(',', array_fill(0, count($unit_ids), '?'));
    $sql = "SELECT Space_ID, BusinessPhoto1, BusinessPhoto2, BusinessPhoto3, BusinessPhoto4, BusinessPhoto5 
            FROM clientspace 
            WHERE Space_ID IN ($placeholders)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($unit_ids);
    $photos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $photos[$row['Space_ID']] = array_values(array_filter([
            $row['BusinessPhoto1'],
            $row['BusinessPhoto2'],
            $row['BusinessPhoto3'],
            $row['BusinessPhoto4'],
            $row['BusinessPhoto5'],
        ]));
    }
    return $photos;
}

public function addUnitPhoto($space_id, $client_id, $filename) {
    $sql = "SELECT BusinessPhoto1, BusinessPhoto2, BusinessPhoto3, BusinessPhoto4, BusinessPhoto5
            FROM clientspace WHERE Space_ID = ? AND Client_ID = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$space_id, $client_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    for ($i = 1; $i <= 5; $i++) {
        if (empty($row["BusinessPhoto$i"])) {
            $update = "UPDATE clientspace SET BusinessPhoto$i = ? WHERE Space_ID = ? AND Client_ID = ?";
            return $this->executeStatement($update, [$filename, $space_id, $client_id]);
        }
    }
    return false;
}

public function deleteUnitPhoto($space_id, $client_id, $photo_filename) {
    $sql = "SELECT BusinessPhoto1, BusinessPhoto2, BusinessPhoto3, BusinessPhoto4, BusinessPhoto5
            FROM clientspace WHERE Space_ID = ? AND Client_ID = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$space_id, $client_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    for ($i = 1; $i <= 5; $i++) {
        if ($row["BusinessPhoto$i"] === $photo_filename) {
            $update = "UPDATE clientspace SET BusinessPhoto$i = NULL WHERE Space_ID = ? AND Client_ID = ?";
            return $this->executeStatement($update, [$space_id, $client_id]);
        }
    }
    return false;
}









public function getInvoiceChatMessagesForClient($invoice_id) {
    $sql = "SELECT ic.*, 
               CASE 
                   WHEN ic.Sender_Type='admin' THEN 'Admin'
                   WHEN ic.Sender_Type='system' THEN 'System'
                   ELSE CONCAT(c.Client_fn, ' ', c.Client_ln) 
               END AS SenderName
        FROM invoice_chat ic
        LEFT JOIN invoice i ON ic.Invoice_ID = i.Invoice_ID
        LEFT JOIN client c ON i.Client_ID = c.Client_ID
        WHERE ic.Invoice_ID = ?
        ORDER BY ic.Created_At ASC";
    return $this->runQuery($sql, [$invoice_id], true);
}

public function nukeClient($client_id) {
    $pdo = $this->pdo;
    $pdo->beginTransaction();
    try {
        // Get all invoice IDs for this client
        $stmt = $pdo->prepare("SELECT Invoice_ID FROM invoice WHERE Client_ID = ?");
        $stmt->execute([$client_id]);
        $invoice_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 1. Delete payment history for those invoices
        if ($invoice_ids) {
            $in_ids = implode(",", array_fill(0, count($invoice_ids), "?"));
            $pdo->prepare("DELETE FROM paymenthistory WHERE Invoice_ID IN ($in_ids)")->execute($invoice_ids);
            $pdo->prepare("DELETE FROM transaction WHERE Invoice_ID IN ($in_ids)")->execute($invoice_ids);
            $pdo->prepare("DELETE FROM invoice_chat WHERE Invoice_ID IN ($in_ids)")->execute($invoice_ids);
            $pdo->prepare("DELETE FROM invoice_chat_seen WHERE Invoice_ID IN ($in_ids)")->execute($invoice_ids);
            $pdo->prepare("DELETE FROM clientfeedback WHERE CS_ID IN ($in_ids)")->execute($invoice_ids);
        }

        // 2. Delete all chats seen by this client as well
        $pdo->prepare("DELETE FROM invoice_chat_seen WHERE Client_ID = ?")->execute([$client_id]);

        // 3. Delete all invoices
        if ($invoice_ids) {
            $in_ids = implode(",", array_fill(0, count($invoice_ids), "?"));
            $pdo->prepare("DELETE FROM invoice WHERE Invoice_ID IN ($in_ids)")->execute($invoice_ids);
        }

        // 4. Delete clientspace (will free spaces for others)
        $pdo->prepare("DELETE FROM clientspace WHERE Client_ID = ?")->execute([$client_id]);

        // 5. Set any spaceavailability to Available for units that were occupied by this client
        $spaces = $pdo->prepare("SELECT Space_ID FROM clientspace WHERE Client_ID = ?");
        $spaces->execute([$client_id]);
        $space_ids = $spaces->fetchAll(PDO::FETCH_COLUMN);
        if ($space_ids) {
            foreach ($space_ids as $sid) {
                $pdo->prepare("UPDATE spaceavailability SET Status = 'Available' WHERE Space_ID = ? AND Status = 'Occupied'")->execute([$sid]);
            }
        }

        // 6. Delete maintenance request history for this client's requests
        $reqs = $pdo->prepare("SELECT Request_ID FROM maintenancerequest WHERE Client_ID = ?");
        $reqs->execute([$client_id]);
        $request_ids = $reqs->fetchAll(PDO::FETCH_COLUMN);
        if ($request_ids) {
            $in_rids = implode(",", array_fill(0, count($request_ids), "?"));
            $pdo->prepare("DELETE FROM maintenancerequeststatushistory WHERE Request_ID IN ($in_rids)")->execute($request_ids);
        }

        // 7. Delete maintenance requests
        $pdo->prepare("DELETE FROM maintenancerequest WHERE Client_ID = ?")->execute([$client_id]);

        // 8. Delete rental requests
        $pdo->prepare("DELETE FROM rentalrequest WHERE Client_ID = ?")->execute([$client_id]);

        // 9. Finally delete client
        $pdo->prepare("DELETE FROM client WHERE Client_ID = ?")->execute([$client_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}





public function setUnitAvailable($space_id) {
    $this->executeStatement(
        "UPDATE space SET Flow_Status = 'new' WHERE Space_ID = ?", 
        [$space_id]
    );
    // Debug log
    $status = $this->runQuery("SELECT Flow_Status FROM space WHERE Space_ID = ?", [$space_id]);
    error_log("setUnitAvailable: After update, Space_ID $space_id Flow_Status: " . print_r($status, true));
    // ...rest of your code
    $this->executeStatement(
        "UPDATE spaceavailability SET Status = 'Available', EndDate = CURDATE() 
         WHERE Space_ID = ? AND Status = 'Occupied' AND EndDate >= CURDATE()", 
        [$space_id]
    );
    $exists = $this->runQuery(
        "SELECT 1 FROM spaceavailability WHERE Space_ID = ? AND Status = 'Available'", 
        [$space_id]
    );
    if (!$exists) {
        $this->executeStatement(
            "INSERT INTO spaceavailability (Space_ID, Status) VALUES (?, 'Available')", 
            [$space_id]
        );
    }
}

public function getInvoicesByFlowStatus($status = 'new') {
    $sql = "SELECT i.*, c.Client_fn, c.Client_ln, s.Name AS UnitName
            FROM invoice i
            LEFT JOIN client c ON i.Client_ID = c.Client_ID
            LEFT JOIN space s ON i.Space_ID = s.Space_ID
            WHERE i.Flow_Status = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function updateInvoiceFlowStatus($invoice_id, $status) {
    $stmt = $this->pdo->prepare("UPDATE invoice SET Flow_Status = ? WHERE Invoice_ID = ?");
    return $stmt->execute([$status, $invoice_id]);
}

// Get all invoices by flow status, e.g. 'new'

public function sendInvoiceChat($invoice_id, $sender_type, $sender_id, $message, $image_path = null) {
    $sql = "INSERT INTO invoice_chat (Invoice_ID, Sender_Type, Sender_ID, Message, Image_Path, Created_At)
            VALUES (?, ?, ?, ?, ?, NOW())";
    return $this->executeStatement($sql, [$invoice_id, $sender_type, $sender_id, $message, $image_path]);
}

public function getInvoiceHistoryForClient($client_id) {
    $sql = "SELECT * FROM invoice WHERE Client_ID = ?";
    return $this->runQuery($sql, [$client_id], true);
}



public function runQueryAll($query, $params = []) {
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    

    // --- Payment History Methods ---
    public function addPaymentHistory($invoice_id, $amount, $method) {
        $sql = "INSERT INTO paymenthistory (Invoice_ID, PaymentDate, Amount, Method) VALUES (?, CURDATE(), ?, ?)";
        return $this->executeStatement($sql, [$invoice_id, $amount, $method]);
    }

    public function getPaymentHistoryByInvoice($invoice_id) {
        $sql = "SELECT * FROM paymenthistory WHERE Invoice_ID = ? ORDER BY PaymentDate DESC";
        return $this->runQuery($sql, [$invoice_id], true);
    }

    // --- Client Authentication & Registration ---
    public function getClientByUsername($username) {
        $sql = "SELECT Client_ID, Client_fn, C_username, C_password, Status FROM client WHERE C_username = ?";
        return $this->runQuery($sql, [$username]);
    }

    public function setClientStatus($client_id, $status) {
        $sql = "UPDATE client SET Status = ? WHERE Client_ID = ?";
        return $this->executeStatement($sql, [$status, $client_id]);
    }

    public function registerClient($fname, $lname, $email, $phone, $username, $hashed_password) {
        $sql = "INSERT INTO client (Client_fn, Client_ln, Client_Email, Client_Phone, C_username, C_password, Status) VALUES (?, ?, ?, ?, ?, ?, 'Active')";
        return $this->executeStatement($sql, [$fname, $lname, $email, $phone, $username, $hashed_password]);
    }

    // --- Homepage & Dashboard Data ---
    public function getClientDetails($client_id) {
        $sql = "SELECT Client_fn, Client_ln, C_username FROM client WHERE Client_ID = ?";
        return $this->runQuery($sql, [$client_id]);
    }

    public function getClientStatus($client_id) {
        $sql = "SELECT Status FROM client WHERE Client_ID = ?";
        return $this->runQuery($sql, [$client_id]);
    }

    public function getClientFullDetails($client_id) {
        $sql = "SELECT Client_fn, Client_ln, C_username, Status FROM client WHERE Client_ID = ?";
        return $this->runQuery($sql, [$client_id]);
    }

  public function getClientsWithLastInvoice() {
        $sql = "SELECT i.Invoice_ID, c.Client_ID, c.Client_fn, c.Client_ln, s.Name AS UnitName,
                       i.InvoiceDate, i.Status,
                       r.EndDate
                FROM invoice i
                LEFT JOIN client c ON i.Client_ID = c.Client_ID
                LEFT JOIN space s ON i.Space_ID = s.Space_ID
                LEFT JOIN rentalrequest r ON r.Client_ID = i.Client_ID AND r.Space_ID = i.Space_ID AND r.Status = 'Accepted'
                WHERE i.Status = 'unpaid'
                ORDER BY r.EndDate DESC, i.InvoiceDate DESC";
        return $this->runQuery($sql, [], true);
    }

    
    public function getRentedUnits($client_id) {
        $sql = "SELECT s.Space_ID, s.Name, s.Price, st.SpaceTypeName, s.Street, s.Brgy, s.City,
                    sa.StartDate, sa.EndDate
                FROM clientspace cs
                JOIN space s ON cs.Space_ID = s.Space_ID
                LEFT JOIN spacetype st ON s.SpaceType_ID = st.SpaceType_ID
                LEFT JOIN spaceavailability sa ON sa.Space_ID = s.Space_ID
                    AND sa.Status = 'Occupied'
                    AND sa.EndDate = (
                        SELECT MAX(sa2.EndDate) FROM spaceavailability sa2
                        WHERE sa2.Space_ID = s.Space_ID AND sa2.Status = 'Occupied'
                    )
                WHERE cs.Client_ID = ?
                ORDER BY sa.EndDate DESC";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function getClientRentedUnitIds($client_id) {
        $sql = "SELECT cs.Space_ID
                FROM clientspace cs
                JOIN spaceavailability sa ON cs.Space_ID = sa.Space_ID
                WHERE cs.Client_ID = ? AND sa.Status='Occupied' AND sa.EndDate >= CURDATE()";
        $results = $this->runQuery($sql, [$client_id], true);
        return $results ? array_column($results, 'Space_ID') : [];
    }

    // --- AVAILABLE UNITS ---
    public function getAvailableUnitsForRental($id) {
        $sql = "SELECT s.Space_ID, s.Name, s.Price, st.SpaceTypeName
                FROM space s
                LEFT JOIN spacetype st ON s.SpaceType_ID = st.SpaceType_ID
                LEFT JOIN spaceavailability sa ON s.Space_ID = sa.Space_ID AND sa.Status = 'Occupied'
                WHERE sa.Status IS NULL AND s.Space_ID = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

  public function getHomepageAvailableUnits($limit = 6) {
    // Only show units that are available to rent (Flow_Status = 'new')
    $sql = "SELECT s.*, st.SpaceTypeName
            FROM space s
            LEFT JOIN spacetype st ON s.SpaceType_ID = st.SpaceType_ID
            LEFT JOIN (
                SELECT Space_ID FROM spaceavailability
                WHERE LOWER(Status) = 'occupied' AND EndDate >= CURDATE()
            ) sa ON s.Space_ID = sa.Space_ID
            WHERE sa.Space_ID IS NULL
              AND s.Flow_Status = 'new' -- Only available units
            ORDER BY s.Space_ID DESC
            LIMIT :limit";
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

public function getHomepageRentedUnits($limit = 12) {
    $sql = "SELECT s.Space_ID, s.Name, s.Price, st.SpaceTypeName, s.Street, s.Brgy, s.City, 
                   sa.StartDate, sa.EndDate, c.Client_fn, c.Client_ln
            FROM space s
            JOIN spaceavailability sa 
                ON s.Space_ID = sa.Space_ID 
                AND sa.Status = 'Occupied' 
                AND sa.EndDate >= CURDATE()
            LEFT JOIN spacetype st ON s.SpaceType_ID = st.SpaceType_ID
            LEFT JOIN clientspace cs ON s.Space_ID = cs.Space_ID
            LEFT JOIN client c ON cs.Client_ID = c.Client_ID
            WHERE s.Flow_Status = 'old'
            ORDER BY sa.EndDate DESC
            LIMIT :limit";
    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) { 
        return []; 
    }
}

    // --- Feedback and Testimonials ---
    public function saveFeedback($invoice_id, $rating, $comments) {
        $sql = "INSERT INTO clientfeedback (CS_ID, Rating, Comments, Dates) VALUES (?, ?, ?, NOW())";
        return $this->executeStatement($sql, [$invoice_id, $rating, $comments]);
    }

    public function getFeedbackPrompts($client_id) {
        $sql = "SELECT i.Invoice_ID, i.InvoiceDate, s.Name AS SpaceName
                FROM invoice i
                JOIN space s ON i.Space_ID = s.Space_ID
                LEFT JOIN clientfeedback f ON f.CS_ID = i.Invoice_ID
                WHERE i.Client_ID = ? AND i.Status = 'kicked' AND f.Feedback_ID IS NULL";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function getHomepageTestimonials($limit = 6) {
        $sql = "SELECT cf.*, c.Client_fn, c.Client_ln
                FROM clientfeedback cf
                JOIN invoice i ON cf.CS_ID = i.Invoice_ID
                JOIN client c ON i.Client_ID = c.Client_ID
                ORDER BY cf.Dates DESC
                LIMIT :limit";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    // --- Invoice and Payment ---
    public function getClientRentedUnitsList($client_id) {
        $sql = "SELECT DISTINCT s.Space_ID, s.Name AS SpaceName
                FROM clientspace cs
                JOIN space s ON cs.Space_ID = s.Space_ID
                WHERE cs.Client_ID = ?
                ORDER BY s.Name";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function getClientInvoiceHistory($client_id) {
        $sql = "SELECT i.Invoice_ID, i.InvoiceDate, i.InvoiceTotal, i.Status, s.Name AS SpaceName, s.Space_ID
                FROM invoice i
                LEFT JOIN space s ON i.Space_ID = s.Space_ID
                WHERE i.Client_ID = ?
                ORDER BY i.InvoiceDate DESC";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function getOverdueInvoices() {
        $sql = "SELECT i.Invoice_ID, i.Client_ID, i.Space_ID, s.Name AS SpaceName
                FROM invoice i
                JOIN space s ON i.Space_ID = s.Space_ID
                WHERE i.Status = 'unpaid' AND i.InvoiceDate < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        try { return $this->pdo->query($sql)->fetchAll(); } catch (PDOException $e) { return []; }
    }

    // --- Rental and Maintenance Requests ---
    public function createRentalRequest($client_id, $space_id, $start_date, $end_date) {
        $sql = "INSERT INTO rentalrequest (Client_ID, Space_ID, StartDate, EndDate, Status, Requested_At)
                VALUES (?, ?, ?, ?, 'Pending', NOW())";
        return $this->executeStatement($sql, [$client_id, $space_id, $start_date, $end_date]);
    }

    public function getClientSpacesForMaintenance($client_id) {
        $sql = "SELECT s.Space_ID, s.Name
                FROM clientspace cs
                JOIN space s ON cs.Space_ID = s.Space_ID
                WHERE cs.Client_ID = ?
                ORDER BY s.Name";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function hasPendingMaintenanceRequest($client_id, $space_id) {
        $sql = "SELECT 1
                FROM maintenancerequest
                WHERE Client_ID = ? AND Space_ID = ? AND Status IN ('Submitted', 'In Progress')";
        return (bool)$this->runQuery($sql, [$client_id, $space_id]);
    }

    public function createMaintenanceRequest($client_id, $space_id) {
        $this->pdo->beginTransaction();
        try {
            $sql1 = "INSERT INTO maintenancerequest (Client_ID, Space_ID, RequestDate, Status)
                     VALUES (?, ?, CURDATE(), 'Submitted')";
            $request_id = $this->insertAndGetId($sql1, [$client_id, $space_id]);
            if (!$request_id) throw new Exception("Failed to create maintenance request.");
            $sql2 = "INSERT INTO maintenancerequeststatushistory (Request_ID, StatusChangeDate, NewStatus)
                     VALUES (?, CURDATE(), 'Submitted')";
            $this->executeStatement($sql2, [$request_id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function sendInvoiceChatWithChatId($chat_id, $sender_type, $sender_id, $message, $image_path = null) {
    $sql = "INSERT INTO invoice_chat (Chat_ID, Sender_Type, Sender_ID, Message, Image_Path, Created_At)
            VALUES (?, ?, ?, ?, ?, NOW())";
    return $this->executeStatement($sql, [$chat_id, $sender_type, $sender_id, $message, $image_path]);
}

    
public function createNextRecurringInvoiceWithChat($invoice_id) {
    // Get the current invoice
    $invoice = $this->runQuery("SELECT * FROM invoice WHERE Invoice_ID = ?", [$invoice_id]);
    if (!$invoice) return false;

    // Get the latest invoice for this client/unit (use EndDate, NOT rentalrequest anymore!)
    $last_invoice = $this->runQuery(
        "SELECT * FROM invoice WHERE Client_ID = ? AND Space_ID = ? ORDER BY EndDate DESC LIMIT 1",
        [$invoice['Client_ID'], $invoice['Space_ID']]
    );
    if (!$last_invoice) return false;

    // Compute new period (add 1 month to last invoice's EndDate)
    $last_end = $last_invoice['EndDate'];
    $start_date = date('Y-m-d', strtotime("$last_end +1 day"));
    $end_date = date('Y-m-d', strtotime("$start_date +1 month -1 day"));

    $this->pdo->beginTransaction();
    try {
        // Insert new invoice and fetch new Invoice_ID (now with EndDate!)
        $this->executeStatement(
            "INSERT INTO invoice (Client_ID, Space_ID, InvoiceDate, EndDate, InvoiceTotal, Status, Flow_Status) VALUES (?, ?, ?, ?, ?, 'unpaid', 'new')",
            [$invoice['Client_ID'], $invoice['Space_ID'], $start_date, $end_date, $invoice['InvoiceTotal']]
        );
        $new_invoice_id = $this->pdo->lastInsertId();

        // Copy all messages from old invoice chat to new invoice chat
        $old_msgs = $this->runQueryAll(
            "SELECT * FROM invoice_chat WHERE Invoice_ID = ? ORDER BY Created_At ASC, Chat_ID ASC",
            [$invoice_id]
        );
        foreach ($old_msgs as $msg) {
            $this->executeStatement(
                "INSERT INTO invoice_chat (Invoice_ID, Sender_Type, Sender_ID, Message, Image_Path, Created_At) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $new_invoice_id,
                    $msg['Sender_Type'],
                    $msg['Sender_ID'],
                    $msg['Message'],
                    $msg['Image_Path'],
                    $msg['Created_At']
                ]
            );
        }

        // Optionally add a system message
        $this->executeStatement(
            "INSERT INTO invoice_chat (Invoice_ID, Sender_Type, Sender_ID, Message, Image_Path, Created_At) VALUES (?, 'system', NULL, ?, NULL, NOW())",
            [$new_invoice_id, 'Conversation continued from previous invoice.']
        );

        $this->pdo->commit();
        return $new_invoice_id;
    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}
    public function getClientMaintenanceHistory($client_id) {
        $sql = "SELECT mr.Request_ID, mr.Space_ID, s.Name AS SpaceName, mr.RequestDate, mr.Status,
                       (SELECT MAX(StatusChangeDate) FROM maintenancerequeststatushistory WHERE Request_ID = mr.Request_ID) AS LastStatusDate,
                       h.Handyman_fn, h.Handyman_ln, mr.Handyman_ID
                FROM maintenancerequest mr
                JOIN space s ON mr.Space_ID = s.Space_ID
                LEFT JOIN handyman h ON mr.Handyman_ID = h.Handyman_ID
                WHERE mr.Client_ID = ?
                ORDER BY mr.RequestDate DESC";
        return $this->runQuery($sql, [$client_id], true);
    }

    public function getMaintenanceHistoryForUnits(array $unit_ids, $client_id) {
        if (empty($unit_ids)) return [];
        $placeholders = implode(',', array_fill(0, count($unit_ids), '?'));
        $sql = "SELECT Space_ID, RequestDate, Status
                FROM maintenancerequest
                WHERE Client_ID = ? AND Space_ID IN ($placeholders)
                ORDER BY RequestDate DESC LIMIT 5";
        $params = array_merge([$client_id], $unit_ids);
        $history = $this->runQuery($sql, $params, true);
        $groupedHistory = [];
        if ($history) {
            foreach ($history as $item) {
                $groupedHistory[$item['Space_ID']][] = $item;
            }
        }
        return $groupedHistory;
    }

    // --- Handyman and Job Types ---
    public function getAllHandymenWithJob() {
        $sql = "SELECT h.Handyman_ID, h.Handyman_fn, h.Handyman_ln, h.Phone, jt.JobType_Name
                FROM handyman h
                LEFT JOIN handymanjob hj ON hj.Handyman_ID = h.Handyman_ID
                LEFT JOIN jobtype jt ON hj.JobType_ID = jt.JobType_ID
                ORDER BY h.Handyman_ln, h.Handyman_fn";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    public function getJobTypeNameById($jobtype_id) {
        $sql = "SELECT JobType_Name FROM jobtype WHERE JobType_ID = ?";
        return $this->runQuery($sql, [$jobtype_id]);
    }

    public function getHandymenByJobType($jobtype_id) {
        $sql = "SELECT h.Handyman_fn, h.Handyman_ln, h.Phone
                FROM handyman h
                INNER JOIN handymanjob hj ON hj.Handyman_ID = h.Handyman_ID
                WHERE hj.JobType_ID = ?
                ORDER BY h.Handyman_ln, h.Handyman_fn";
        return $this->runQuery($sql, [$jobtype_id], true) ?: [];
    }

    public function getHandymanById($handyman_id) {
        $sql = "SELECT h.Handyman_ID, h.Handyman_fn, h.Handyman_ln, h.Phone, hj.JobType_ID
                FROM handyman h
                LEFT JOIN handymanjob hj ON hj.Handyman_ID = h.Handyman_ID
                WHERE h.Handyman_ID = ? LIMIT 1";
        return $this->runQuery($sql, [$handyman_id]);
    }

    public function addHandyman($fn, $ln, $phone, $jobtype_id) {
        $this->pdo->beginTransaction();
        try {
            $sql1 = "INSERT INTO handyman (Handyman_fn, Handyman_ln, Phone) VALUES (?,?,?)";
            $handyman_id = $this->insertAndGetId($sql1, [$fn, $ln, $phone]);
            if (!$handyman_id) throw new Exception("Failed to create handyman record.");

            $sql2 = "INSERT INTO handymanjob (Handyman_ID, JobType_ID) VALUES (?, ?)";
            $this->executeStatement($sql2, [$handyman_id, $jobtype_id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function removeSpacePhoto($space_id) {
    $sql = "UPDATE space SET Photo = NULL WHERE Space_ID = ?";
    return $this->executeStatement($sql, [$space_id]);
}

    public function updateHandyman($id, $fn, $ln, $phone, $jobtype_id) {
        $this->pdo->beginTransaction();
        try {
            $sql1 = "UPDATE handyman SET Handyman_fn=?, Handyman_ln=?, Phone=? WHERE Handyman_ID=?";
            $this->executeStatement($sql1, [$fn, $ln, $phone, $id]);

            $job_exists = $this->runQuery("SELECT 1 FROM handymanjob WHERE Handyman_ID = ?", [$id]);

            if ($job_exists) {
                $sql2 = "UPDATE handymanjob SET JobType_ID=? WHERE Handyman_ID=?";
                $this->executeStatement($sql2, [$jobtype_id, $id]);
            } else {
                $sql2 = "INSERT INTO handymanjob (Handyman_ID, JobType_ID) VALUES (?, ?)";
                $this->executeStatement($sql2, [$id, $jobtype_id]);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteHandyman($handyman_id) {
        $this->pdo->beginTransaction();
        try {
            $this->executeStatement("DELETE FROM handymanjob WHERE Handyman_ID = ?", [$handyman_id]);
            $this->executeStatement("DELETE FROM handyman WHERE Handyman_ID = ?", [$handyman_id]);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getAllJobTypes() {
        $sql = "SELECT JobType_ID, JobType_Name FROM jobtype ORDER BY JobType_Name";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addJobType($jobtype_name) {
        $stmt = $this->pdo->query("SELECT MAX(JobType_ID) as max_id FROM jobtype");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_id = $row['max_id'] + 1;
        $sql = "INSERT INTO jobtype (JobType_ID, JobType_Name) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$next_id, $jobtype_name]);
    }

    // --- Admin Actions ---
    public function kickClientFromUnit($invoice_id, $client_id, $space_id) {
        $this->pdo->beginTransaction();
        try {
            $this->executeStatement("DELETE FROM clientspace WHERE Client_ID = ? AND Space_ID = ?", [$client_id, $space_id]);
            $this->executeStatement("UPDATE spaceavailability SET Status = 'Available', EndDate = CURDATE() WHERE Space_ID = ? AND Status = 'Occupied'", [$space_id]);
            $this->executeStatement("UPDATE invoice SET Status = 'kicked' WHERE Invoice_ID = ?", [$invoice_id]);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to kick client $client_id for invoice $invoice_id: " . $e->getMessage());
            return false;
        }
    }

     public function addSpaceType($spaceTypeName) {
        $sql = "INSERT INTO spacetype (SpaceTypeName) VALUES (?)";
        return $this->executeStatement($sql, [$spaceTypeName]);
    }
    
    public function getAllSpaceTypes() {
        $sql = "SELECT SpaceType_ID, SpaceTypeName FROM spacetype ORDER BY SpaceTypeName ASC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function isSpaceNameExists($name) {
        $sql = "SELECT 1 FROM space WHERE LOWER(TRIM(Name)) = LOWER(TRIM(?))";
        return (bool)$this->runQuery($sql, [$name]);
    }
public function addNewSpace($name, $spacetype_id, $ua_id, $price, $photo_filename = null) {
    $street = 'General Luna Strt';
    $brgy = '10';
    $city = 'Lipa City';
    $avail_status = 'Available';

    $this->pdo->beginTransaction();
    try {
        $sql1 = "INSERT INTO space (Name, SpaceType_ID, UA_ID, Street, Brgy, City, Photo, Price) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $space_id = $this->insertAndGetId($sql1, [
            $name, 
            $spacetype_id, 
            $ua_id, 
            $street, 
            $brgy, 
            $city, 
            $photo_filename, 
            $price
        ]);

        if (!$space_id) {
            throw new Exception("Failed to create space record.");
        }

        $sql2 = "INSERT INTO spaceavailability (Space_ID, Status) VALUES (?, ?)";
        $this->executeStatement($sql2, [$space_id, $avail_status]);

        $this->pdo->commit();
        return true;
    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}

    public function getAllSpacesWithDetails() {
        $sql = "SELECT s.*, t.SpaceTypeName 
                FROM space s
                LEFT JOIN spacetype t ON s.SpaceType_ID = t.SpaceType_ID
                ORDER BY s.Space_ID DESC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }

        
    }
    
    
    
    // --- Overdue Rentals For Kicking ---
    public function getOverdueRentalsForKicking() {
        $sql = "SELECT i.Invoice_ID, i.Client_ID, i.Space_ID, i.InvoiceDate, 
                       i.Status, c.Client_fn, c.Client_ln, s.Name as SpaceName, r.EndDate, r.Request_ID
                FROM invoice i
                JOIN client c ON i.Client_ID = c.Client_ID
                JOIN space s ON i.Space_ID = s.Space_ID
                JOIN rentalrequest r ON i.Client_ID = r.Client_ID AND i.Space_ID = r.Space_ID
                WHERE i.Status = 'unpaid'
                  AND i.InvoiceDate <= CURDATE()
                  AND r.EndDate <= CURDATE()
                  AND r.Status = 'Accepted'
                ORDER BY r.EndDate ASC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

public function kickOverdueClient($invoice_id, $client_id, $space_id, $request_id) {
    $this->pdo->beginTransaction();
    try {
        // 1. Remove the client-space relationship (client is no longer renting the space)
        $this->executeStatement(
            "DELETE FROM clientspace WHERE Client_ID = ? AND Space_ID = ?", 
            [$client_id, $space_id]
        );

        // 2. Set spaceavailability to 'Available' and set EndDate to today
        $this->executeStatement(
            "UPDATE spaceavailability 
             SET Status = 'Available', EndDate = CURDATE() 
             WHERE Space_ID = ? AND Status = 'Occupied'", 
            [$space_id]
        );

        // 3. Mark the invoice as 'kicked' and set Flow_Status to 'done'
        $this->executeStatement(
            "UPDATE invoice SET Status = 'kicked', Flow_Status = 'done' WHERE Invoice_ID = ?", 
            [$invoice_id]
        );

        // 4. Mark the rental request as 'Rejected'
        $this->executeStatement(
            "UPDATE rentalrequest SET Status = 'Rejected' WHERE Request_ID = ?", 
            [$request_id]
        );

        // 5. Set the space as available in the flow (Flow_Status: 'new')
        $this->executeStatement(
            "UPDATE space SET Flow_Status = 'new' WHERE Space_ID = ?", 
            [$space_id]
        );

        // 6. Ensure there is an 'Available' record in spaceavailability for this space (avoid duplicates)
        $exists = $this->runQuery(
            "SELECT 1 FROM spaceavailability WHERE Space_ID = ? AND Status = 'Available'", 
            [$space_id]
        );
        if (!$exists) {
            $this->executeStatement(
                "INSERT INTO spaceavailability (Space_ID, Status) VALUES (?, 'Available')", 
                [$space_id]
            );
        }

        $this->pdo->commit();
        return true;
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        error_log("Failed to kick client (Invoice #{$invoice_id}): " . $e->getMessage());
        return false;
    }
}

    public function getMaintenanceRequestStatus($request_id) {
        $sql = "SELECT Status FROM maintenancerequest WHERE Request_ID = ?";
        $result = $this->runQuery($sql, [$request_id]);
        return $result ? $result['Status'] : false;
    }

    public function updateMaintenanceStatus($request_id, $new_status) {
        $this->pdo->beginTransaction();
        try {
            $sql1 = "UPDATE maintenancerequest SET Status = ? WHERE Request_ID = ?";
            $this->executeStatement($sql1, [$new_status, $request_id]);
            $sql2 = "INSERT INTO maintenancerequeststatushistory (Request_ID, StatusChangeDate, NewStatus) VALUES (?, NOW(), ?)";
            $this->executeStatement($sql2, [$request_id, $new_status]);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getPendingRentalRequests() {
        $sql = "SELECT r.Request_ID, c.Client_fn, c.Client_ln, s.Name, r.StartDate, r.EndDate, r.Status
                FROM rentalrequest r
                JOIN client c ON r.Client_ID = c.Client_ID
                JOIN space s ON r.Space_ID = s.Space_ID
                WHERE r.Status = 'Pending'
                ORDER BY r.Requested_At ASC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAdminByUsername($username) {
        $sql = "SELECT UA_ID, username, password FROM useraccounts WHERE username = ? AND Type = 'Admin'";
        return $this->runQuery($sql, [$username]);
    }

    public function getAdminDashboardCounts() {
        $sql = "SELECT
                  (SELECT COUNT(*) FROM rentalrequest WHERE Status='Pending') AS pending_rentals,
                  (SELECT COUNT(*) FROM maintenancerequest WHERE Status='Submitted') AS pending_maintenance,
                  (SELECT COUNT(*) FROM invoice WHERE Status = 'unpaid') AS unpaid_invoices,
                  (SELECT COUNT(*) FROM invoice WHERE Status = 'unpaid' AND InvoiceDate <= CURDATE()) AS unpaid_due_invoices";
        return $this->runQuery($sql);
    }

    public function getLatestPendingRequests($limit = 5) {
        $sql = "SELECT rr.Request_ID, c.Client_fn, c.Client_ln, s.Name AS UnitName, 
                       rr.StartDate, rr.EndDate, rr.Status, rr.Requested_At
                FROM rentalrequest rr
                LEFT JOIN client c ON rr.Client_ID = c.Client_ID
                LEFT JOIN space s ON rr.Space_ID = s.Space_ID
                WHERE rr.Status = 'Pending'
                ORDER BY rr.Requested_At DESC
                LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

  

public function markInvoiceAsPaid($invoice_id) {
    $invoice_details = $this->runQuery("SELECT Space_ID, InvoiceTotal FROM invoice WHERE Invoice_ID = ?", [$invoice_id]);
    if (!$invoice_details) {
        return false;
    }

    $this->pdo->beginTransaction();
    try {
        // Update both Status and Flow_Status
        $this->executeStatement(
            "UPDATE invoice SET Status = 'paid', Flow_Status = 'done' WHERE Invoice_ID = ?",
            [$invoice_id]
        );

        $this->executeStatement(
            "INSERT INTO transaction (Space_ID, Invoice_ID, TransactionDate, Total_Amount) VALUES (?, ?, CURDATE(), ?)",
            [$invoice_details['Space_ID'], $invoice_id, $invoice_details['InvoiceTotal']]
        );

        $this->pdo->commit();
        return true;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}
public function getRecentFreeMessages($limit = 5) {
    $sql = "SELECT * FROM free_message WHERE is_deleted = 0 ORDER BY Sent_At DESC LIMIT ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

public function getAllFreeMessages() {
    $sql = "SELECT * FROM free_message ORDER BY Sent_At DESC";
    return $this->runQueryAll($sql);
}

public function insertFreeMessage($name, $email, $phone, $message) {
    $sql = "INSERT INTO free_message (Client_Name, Client_Email, Client_Phone, Message_Text)
            VALUES (?, ?, ?, ?)";
    return $this->executeStatement($sql, [$name, $email, $phone, $message]);
}


public function getSingleInvoiceForDisplay($invoice_id) {
    $sql = "SELECT i.*, c.Client_fn, c.Client_ln, s.Name AS UnitName
            FROM invoice i
            LEFT JOIN client c ON i.Client_ID = c.Client_ID
            LEFT JOIN space s ON i.Space_ID = s.Space_ID
            WHERE i.Invoice_ID = ?
            LIMIT 1";
    $result = $this->runQuery($sql, [$invoice_id]);
    return $result;
}

    public function getAllUnpaidInvoices() {
        $sql = "SELECT i.Invoice_ID, c.Client_fn, c.Client_ln, s.Name AS UnitName,
                       i.InvoiceDate, i.Status, r.EndDate
                FROM invoice i
                INNER JOIN client c ON i.Client_ID = c.Client_ID
                INNER JOIN space s ON i.Space_ID = s.Space_ID
                LEFT JOIN rentalrequest r ON r.Client_ID = i.Client_ID AND r.Space_ID = i.Space_ID AND r.Status = 'Accepted'
                WHERE i.Status = 'unpaid'
                ORDER BY r.EndDate DESC, i.InvoiceDate ASC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // --- Rental Request ---
public function acceptRentalRequest($request_id) {
    $request_details = $this->runQuery(
        "SELECT r.Client_ID, r.Space_ID, r.StartDate, r.EndDate, s.Price 
         FROM rentalrequest r 
         JOIN space s ON r.Space_ID = s.Space_ID
         WHERE r.Request_ID = ? AND r.Status = 'Pending'",
        [$request_id]
    );

    if (!$request_details) {
        return false;
    }

    $this->pdo->beginTransaction();
    try {
        // 1. Mark request as accepted
        $this->executeStatement(
            "UPDATE rentalrequest SET Status = 'Accepted' WHERE Request_ID = ?",
            [$request_id]
        );
        // 2. Mark space as occupied in availability
        $this->executeStatement(
            "INSERT INTO spaceavailability (Space_ID, StartDate, EndDate, Status) VALUES (?, ?, ?, 'Occupied')",
            [$request_details['Space_ID'], $request_details['StartDate'], $request_details['EndDate']]
        );
        // 3. Ensure clientspace link exists
        $is_linked = $this->runQuery("SELECT 1 FROM clientspace WHERE Space_ID = ? AND Client_ID = ?", [$request_details['Space_ID'], $request_details['Client_ID']]);
        if (!$is_linked) {
            $this->executeStatement(
                "INSERT INTO clientspace (Space_ID, Client_ID) VALUES (?, ?)",
                [$request_details['Space_ID'], $request_details['Client_ID']]
            );
        }
        // 4. Create the invoice (with EndDate and Flow_Status)
        $this->executeStatement(
            "INSERT INTO invoice (Client_ID, Space_ID, InvoiceDate, EndDate, InvoiceTotal, Status, Flow_Status) VALUES (?, ?, ?, ?, ?, 'unpaid', 'new')",
            [
                $request_details['Client_ID'],
                $request_details['Space_ID'],
                $request_details['StartDate'],
                $request_details['EndDate'],
                $request_details['Price']
            ]
        );
        // 5. Update space flow status to 'old' (not available anymore)
        $this->executeStatement(
            "UPDATE space SET Flow_Status = 'old' WHERE Space_ID = ?",
            [$request_details['Space_ID']]
        );
        $this->pdo->commit();
        return true;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        return false;
    }
}

    public function getActiveMaintenanceRequests() {
        $sql = "SELECT mr.Request_ID, c.Client_fn, c.Client_ln, s.Name AS SpaceName, 
                       mr.RequestDate, mr.Status, mr.Handyman_ID,
                       h.Handyman_fn, h.Handyman_ln,
                       (SELECT GROUP_CONCAT(jt.JobType_Name SEPARATOR ', ')
                        FROM handymanjob hj
                        JOIN jobtype jt ON hj.JobType_ID = jt.JobType_ID
                        WHERE hj.Handyman_ID = h.Handyman_ID) AS JobTypes
                FROM maintenancerequest mr
                JOIN client c ON mr.Client_ID = c.Client_ID
                JOIN space s ON mr.Space_ID = s.Space_ID
                LEFT JOIN handyman h ON mr.Handyman_ID = h.Handyman_ID
                WHERE mr.Status IN ('Submitted', 'In Progress')
                ORDER BY mr.RequestDate DESC";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllHandymenWithJobTypes() {
        $sql = "SELECT h.Handyman_ID, h.Handyman_fn, h.Handyman_ln, 
                       GROUP_CONCAT(jt.JobType_Name SEPARATOR ', ') AS JobTypes
                FROM handyman h
                LEFT JOIN handymanjob hj ON h.Handyman_ID = hj.Handyman_ID
                LEFT JOIN jobtype jt ON hj.JobType_ID = jt.JobType_ID
                GROUP BY h.Handyman_ID
                ORDER BY h.Handyman_fn";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateMaintenanceRequest($request_id, $new_status, $handyman_id) {
        $current = $this->runQuery("SELECT Status FROM maintenancerequest WHERE Request_ID = ?", [$request_id]);
        $status_changed = ($current && $current['Status'] !== $new_status);

        $this->pdo->beginTransaction();
        try {
            $sql1 = "UPDATE maintenancerequest SET Status = ?, Handyman_ID = ? WHERE Request_ID = ?";
            $this->executeStatement($sql1, [$new_status, $handyman_id, $request_id]);
            if ($status_changed) {
                $sql2 = "INSERT INTO maintenancerequeststatushistory (Request_ID, StatusChangeDate, NewStatus) VALUES (?, NOW(), ?)";
                $this->executeStatement($sql2, [$request_id, $new_status]);
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Failed to update maintenance request #{$request_id}: " . $e->getMessage());
            return false;
        }
    }

    public function updateClientStatus($client_id, $status) {
        $sql = "UPDATE client SET Status = ? WHERE Client_ID = ?";
        return $this->executeStatement($sql, [$status, $client_id]);
    }

    public function hardDeleteClient($client_id) {
        $this->pdo->beginTransaction();
        try {
            $rented_spaces = $this->runQuery("SELECT Space_ID FROM clientspace WHERE Client_ID = ?", [$client_id], true);
            if ($rented_spaces) {
                foreach ($rented_spaces as $space) {
                    $this->executeStatement(
                        "UPDATE spaceavailability SET Status = 'available' WHERE Space_ID = ? AND Status = 'Occupied'",
                        [$space['Space_ID']]
                    );
                }
            }
            $invoices = $this->runQuery("SELECT Invoice_ID FROM invoice WHERE Client_ID = ?", [$client_id], true);
            $invoice_ids = $invoices ? array_column($invoices, 'Invoice_ID') : [];

            if (!empty($invoice_ids)) {
                $placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
                $this->executeStatement("DELETE FROM transaction WHERE Invoice_ID IN ($placeholders)", $invoice_ids);
                $this->executeStatement("DELETE FROM clientfeedback WHERE CS_ID IN ($placeholders)", $invoice_ids);
            }

            $this->executeStatement("DELETE FROM clientspace WHERE Client_ID = ?", [$client_id]);
            $this->executeStatement("DELETE FROM maintenancerequest WHERE Client_ID = ?", [$client_id]);
            $this->executeStatement("DELETE FROM invoice WHERE Client_ID = ?", [$client_id]);
            $this->executeStatement("DELETE FROM rentalrequest WHERE Client_ID = ?", [$client_id]);
            $this->executeStatement("DELETE FROM client WHERE Client_ID = ?", [$client_id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // --- Methods for a single Unit/Space ---
    public function isUnitRented($space_id) {
        $sql = "SELECT 1 FROM clientspace WHERE Space_ID = ? LIMIT 1";
        return (bool)$this->runQuery($sql, [$space_id]);
    }

    public function updateUnit_price($space_id, $price) {
        $sql = "UPDATE space SET Price = ? WHERE Space_ID = ?";
        return $this->executeStatement($sql, [$price, $space_id]);
    }

    public function hardDeleteUnit($space_id) {
        $this->pdo->beginTransaction();
        try {
            $this->executeStatement("DELETE FROM spaceavailability WHERE Space_ID = ?", [$space_id]);
            $this->executeStatement("DELETE FROM clientspace WHERE Space_ID = ?", [$space_id]);
            $this->executeStatement("DELETE FROM rentalrequest WHERE Space_ID = ?", [$space_id]);
            $this->executeStatement("DELETE FROM maintenancerequest WHERE Space_ID = ?", [$space_id]);
            $this->executeStatement("DELETE FROM space WHERE Space_ID = ?", [$space_id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // --- Methods for Displaying Data on the Page ---
    public function getAllClientsWithAssignedUnit() {
        $sql = "SELECT c.Client_ID, c.Client_fn, c.Client_ln, c.Client_Email, c.C_username, 
                       c.Status, s.Name AS SpaceName
                FROM client c
                LEFT JOIN clientspace cs ON c.Client_ID = cs.Client_ID
                LEFT JOIN space s ON cs.Space_ID = s.Space_ID
                ORDER BY c.Client_ID DESC";
        return $this->runQuery($sql, [], true);
    }

    public function getAllUnitsWithRenterInfo() {
        $sql = "SELECT s.Space_ID, s.Name, s.SpaceType_ID, st.SpaceTypeName, s.Price,
                       c.Client_fn, c.Client_ln
                FROM space s
                LEFT JOIN spacetype st ON s.SpaceType_ID = st.SpaceType_ID
                LEFT JOIN clientspace cs ON s.Space_ID = cs.Space_ID
                LEFT JOIN client c ON cs.Client_ID = c.Client_ID";
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }


public function markRentalRequestDone($client_id, $space_id) {
    // Get the latest accepted rentalrequest for this client/space
    $latest = $this->runQuery(
        "SELECT Request_ID FROM rentalrequest 
         WHERE Client_ID = ? AND Space_ID = ? AND Status = 'Accepted'
         ORDER BY EndDate DESC LIMIT 1",
        [$client_id, $space_id]
    );
    if ($latest && isset($latest['Request_ID'])) {
        $this->executeStatement(
            "UPDATE rentalrequest SET Flow_Status = 'done' WHERE Request_ID = ?",
            [$latest['Request_ID']]
        );
    }
}



    public function getSpacePhoto($space_id) {
    $sql = "SELECT Photo FROM space WHERE Space_ID = ?";
    return $this->runQuery($sql, [$space_id]);
}

public function updateSpacePhoto($space_id, $photo_filename) {
    $sql = "UPDATE space SET Photo = ? WHERE Space_ID = ?";
    return $this->executeStatement($sql, [$photo_filename, $space_id]);
}

    public function rejectRentalRequest($request_id) {
        $sql = "UPDATE rentalrequest SET Status = 'Rejected' WHERE Request_ID = ? AND Status = 'Pending'";
        return $this->executeStatement($sql, [$request_id]);
    }

    public function checkClientCredentialExists($field, $value) {
        if (!in_array($field, ['Client_Email', 'C_username'])) {
            return false;
        }
        $sql = "SELECT 1 FROM client WHERE {$field} = ? LIMIT 1";
        $result = $this->runQuery($sql, [$value]);
        return (bool)$result;
    }

    

}

?>

