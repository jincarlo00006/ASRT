<?php
// Require your single database file. Adjust path if needed.
require 'database/database.php';
session_start();

$db = new Database();

$is_logged_in = isset($_SESSION['client_id']);

$job_types = $db->getAllJobTypes();

$icon_map = [
    "CARPENTRY" => "IMG/show/CARPENTRY.png",
    "ELECTRICAL" => "IMG/show/ELECTRICAL.png",
    "PLUMBING" => "IMG/show/PLUMBING.png",
    "PAINTING" => "IMG/show/PAINTING.png",
    "APPLIANCE REPAIR" => "IMG/show/APPLIANCE.png",
];
?>
<!doctype html>
<html lang="en">
<head>
    <title>Handyman Types</title>
    <?php require('links.php'); ?>
</head>
<body>
<?php require('header.php'); ?>
<div class="container my-5">
    <h2 class="mb-4 text-center fw-bold h-font">Choose the worker for your problem</h2>
    <?php if(!$is_logged_in): ?>
      <div class="alert alert-danger text-center fw-bold fs-5 mb-4">
          Please login first to access the handyman section.
      </div>
      <div class="text-center mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
          Login
        </button>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
          loginModal.show();
        });
      </script>
    <?php endif; ?>

    <div class="row justify-content-evenly px-lg-0 px-md-0 px-5">
    <?php
    if (!empty($job_types)) {
        foreach ($job_types as $row) {
            $name_upper = strtoupper($row['JobType_Name']);
            $img_src = isset($icon_map[$name_upper]) ? $icon_map[$name_upper] : "IMG/show/wifi.png"; // default icon
            
            echo '<div class="col-lg-2 col-md-3 col-sm-6 text-center bg-white rounded shadow py-4 my-3 mx-2">';
            echo '<form method="get" action="handyman.php">';
            echo '<input type="hidden" name="jobtype_id" value="'.htmlspecialchars($row['JobType_ID']).'">';
            
            if(!$is_logged_in) {
                echo '<button type="button" class="btn btn-light w-100" disabled>';
            } else {
                echo '<button type="submit" style="background:none;border:none;padding:0;cursor:pointer;">';
            }
            
            echo '<img src="'.$img_src.'" width="80px" alt="'.htmlspecialchars($row['JobType_Name']).' Icon">';
            echo '<h5 class="mt-3">'.htmlspecialchars($row['JobType_Name']).'</h5>';
            echo '</button>';
            echo '</form>';
            echo '</div>';
        }
    } else {
        echo '<div class="col-12 text-center"><p class="text-muted">No job types available at this time.</p></div>';
    }
    ?>
    </div>
    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </div>
</div>
<?php require('footer.php'); ?>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>