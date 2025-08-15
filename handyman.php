<?php
require 'database/database.php';
session_start();

$db = new Database();

if (!isset($_SESSION['client_id'])) {
    // This is a simple but effective way to block access without a full page structure.
    echo '<!doctype html><html lang="en"><head><title>Login Required</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container my-5"><div class="alert alert-danger text-center fw-bold fs-5">Please login first to access the handyman section.</div><div class="text-center mt-3"><a href="login.php" class="btn btn-primary">Login</a> <a href="index.php" class="btn btn-secondary">Back to Home</a></div></div></body></html>';
    exit();
}

// --- Parameter Validation ---
if (!isset($_GET['jobtype_id']) || !is_numeric($_GET['jobtype_id'])) {
    header("Location: index.php");
    exit();
}
$jobtype_id = intval($_GET['jobtype_id']);

// --- Fetch Data using new Database Methods ---
$jobtype_record = $db->getJobTypeNameById($jobtype_id);
$jobtype_name = $jobtype_record ? $jobtype_record['JobType_Name'] : 'Unknown';

$handymen = $db->getHandymenByJobType($jobtype_id);


// --- Presentation Logic (stays in this file) ---
$icon_map = [
    "CARPENTRY" => "IMG/show/CARPENTRY.png",
    "ELECTRICAL" => "IMG/show/ELECTRICAL.png",
    "PLUMBING" => "IMG/show/PLUMBING.png",
    "PAINTING" => "IMG/show/PAINTING.png",
    "APPLIANCE REPAIR" => "IMG/show/APPLIANCE.png",
];
$img_src = isset($icon_map[strtoupper($jobtype_name)]) ? $icon_map[strtoupper($jobtype_name)] : "IMG/show/wifi.png";

?>
<!doctype html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($jobtype_name) ?> Handymen</title>
    <?php require('links.php'); ?>
</head>
<body>
<?php require('header.php'); ?>
<div class="container my-5">
    <div class="text-center mb-4">
        <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($jobtype_name) ?>" width="100" />
        <h2 class="fw-bold h-font mt-3"><?= htmlspecialchars($jobtype_name) ?> Handymen</h2>
    </div>

    <?php if (!empty($handymen)): ?>
        <div class="row">
            <?php foreach ($handymen as $hm): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($hm['Handyman_fn'] . ' ' . $hm['Handyman_ln']) ?></h5>
                            <p>
                                <strong>Phone:</strong><br>
                                <a href="tel:<?= htmlspecialchars($hm['Phone']) ?>" class="btn btn-success btn-sm mb-1"><?= htmlspecialchars($hm['Phone']) ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No handymen available for this job type at this time.
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="handyman_type.php" class="btn btn-secondary">Back to Job Types</a>
    </div>
</div>
<?php require('footer.php'); ?>
</body>
</html>