<?php
require 'database/database.php';
session_start();

// Create an instance of the Database class
$db = new Database();

$is_logged_in = isset($_SESSION['client_id']);
$client_username = '';
$client_is_inactive = false; 

// --- Session and Status Handling ---
if ($is_logged_in) {
    $client_id = $_SESSION['client_id'];
    if (isset($_SESSION['C_username'])) {
        $client_username = $_SESSION['C_username'];
        if (!isset($_SESSION['C_status'])) {
            $status_record = $db->getClientStatus($client_id);
            if ($status_record) {
                $_SESSION['C_status'] = $status_record['Status'];
                $client_is_inactive = ($status_record['Status'] == 0 || $status_record['Status'] === 'inactive');
            }
        } else {
            $client_is_inactive = ($_SESSION['C_status'] == 0 || $_SESSION['C_status'] === 'inactive');
        }
    } else {
        $details = $db->getClientFullDetails($client_id);
        if ($details) {
            $_SESSION['C_username'] = $details['C_username'];
            $_SESSION['C_status'] = $details['Status'];
            $client_username = $details['Client_fn'] && $details['Client_ln'] ? "{$details['Client_fn']} {$details['Client_ln']}" : $details['C_username'];
            $client_is_inactive = ($details['Status'] == 0 || $details['Status'] === 'inactive');
        }
    }
}

// --- Fetch ALL Data for the Page Using Clean Methods ---

// For personalizing the "Available Units" view
$hide_client_rented_unit_ids = $is_logged_in ? $db->getClientRentedUnitIds($_SESSION['client_id']) : [];
$available_units = $db->getHomepageAvailableUnits(10); // uses improved SQL for case-insensitive status
$rented_units_display = $db->getHomepageRentedUnits(10);
$job_types_display = $db->getAllJobTypes();
$testimonials = $db->getHomepageTestimonials(6);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ASRT Website - HOME</title>
    <?php require('links.php'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Merienda&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sticky-back-btn {
            position: fixed;
            top: 80px;
            right: 30px;
            z-index: 1050;
        }
        @media (max-width: 576px) {
            .sticky-back-btn {
                top: 50px !important;
                right: 10px !important;
            }
        }
        .unit-photo {
            width: 100%;
            max-width: 90%;
            max-height: 180px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        /* Free message bubble style */
        #freeMsgBubble {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            background: #0d6efd;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 30px;
        }
        #freeMsgBubble:hover {
            background: #0b5ed7;
        }
    </style>
</head>



<?php if (isset($_GET['free_msg']) && $_GET['free_msg'] === 'sent'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Message Sent!',
        text: 'We\'ll be there for you shortly.',
        confirmButtonColor: '#3085d6'
    });
});
</script>
<?php endif; ?>

<body class="bg-light">

<?php
// This is the correct place to handle flash messages like login errors.
if (isset($_SESSION['login_error'])) {
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Login Failed', 
                    text: '" . addslashes($_SESSION['login_error']) . "' 
                });
            });
          </script>";
    unset($_SESSION['login_error']);
}
?>
<?php require('header.php'); ?>

<?php if ($is_logged_in && $client_username): ?>
    <div class="sticky-back-btn">
        <a href="dashboard.php" class="btn btn-primary shadow fw-bold">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
<?php endif; ?>

<?php if ($is_logged_in && $client_is_inactive): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Account Inactive',
        text: 'Your account is currently inactive. Please contact the administrator.',
        confirmButtonColor: '#d33'
    });
});
</script>
<?php endif; ?>

<!-- Swiper Carousel -->
<div class="container-fluid px-lg-4 mt-4">
    <div class="swiper swiper-container">
        <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="IMG/show/One.jfif" class="w-100 d-block" alt="Slide 1"></div>
            <div class="swiper-slide"><img src="IMG/show/two.jfif" class="w-100 d-block" alt="Slide 2"></div>
            <div class="swiper-slide"><img src="IMG/show/three.jfif" class="w-100 d-block" alt="Slide 3"></div>
        </div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<div class="container mt-5">
    <div class="row">
        <!-- Available Units -->
        <div class="container mt-5">
        <h2 class="pt-4 mb-4 text-center fw-bold h-font">AVAILABLE UNIT</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 justify-content-center">
                <?php
                if (!empty($available_units)) {
                    $modal_counter = 0;
                    foreach ($available_units as $space) {
                        // Skip displaying units that the current client is already renting
                        if (in_array($space['Space_ID'], $hide_client_rented_unit_ids)) {
                            continue;
                        }
                        $modal_counter++;
                        $modal_id = "negotiateModal" . $modal_counter;
                        ?>
                        <div class="col">
                            <div class="card border-0 shadow" style="max-width: 350px; margin: auto;">
                                <?php if (!empty($space['Photo'])): ?>
                                    <div class="card-img-top text-center" style="padding: 20px 0;">
                                        <img src="uploads/unit_photos/<?= htmlspecialchars($space['Photo']) ?>"
                                            alt="Unit Photo"
                                            class="unit-photo">
                                    </div>
                                <?php else: ?>
                                    <div class="card-img-top text-center py-5" style="font-size:72px;color:#0d6efd;">
                                        <i class="fa-solid fa-house"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($space['Name']) ?></h5>
                                    <h6 class="mb-4">₱<?= number_format($space['Price'], 0) ?> a month</h6>
                                    <div class="feature mb-4">
                                        <h6 class="mb-1">Unit Type</h6>
                                        <span class="badge rounded-pill text-dark text-wrap"><?= htmlspecialchars($space['SpaceTypeName']) ?></span>
                                        <h6 class="mt-3 mb-1">Location</h6>
                                        <span class="badge rounded-pill text-dark text-wrap"><?= htmlspecialchars($space['Street']) ?>, <?= htmlspecialchars($space['Brgy']) ?>, <?= htmlspecialchars($space['City']) ?></span>
                                        <h6 class="mt-3 mb-1">Rating</h6>
                                        <span class="badge rounded-pill bg-light">
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-evenly mb-2">
                                        <?php if ($is_logged_in && !$client_is_inactive): ?>
                                            <button
                                                class="btn btn-sm btn-danger shadow-none rent-btn" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#<?= $modal_id ?>"
                                            >Rent Now</button>
                                        <?php elseif ($is_logged_in && $client_is_inactive): ?>
                                            <button class="btn btn-sm btn-secondary shadow-none" disabled>
                                                Rent Now
                                            </button>
                                        <?php else: ?>
                                            <button
                                                class="btn btn-sm btn-danger shadow-none"
                                                data-bs-toggle="modal"
                                                data-bs-target="#loginPopModal"
                                            >Rent Now</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Negotiation Modal (only render if client is active) -->
                        <?php if ($is_logged_in && !$client_is_inactive): ?>
                        <div class="modal fade" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_id ?>Label" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="<?= $modal_id ?>Label">Contact Admin to Rent: <?= htmlspecialchars($space['Name']) ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <p>
                                            To rent this unit and receive an invoice, please contact the admin for rental approval.<br>
                                        </p>
                                        <div class="alert alert-info mb-3">
                                            <strong>Admin Contact:</strong><br>
                                            <i class="bi bi-envelope"></i> <a href="mailto:rom_telents@asrt.com">rom_telents@asrt.com</a><br>
                                            <i class="bi bi-telephone"></i> <a href="tel:+639171234567">+63 917 123 4567</a>
                                        </div>
                                        <div class="alert alert-warning">
                                            <strong>INVOICE:</strong> Please request your invoice from the admin for the rental.
                                        </div>
                                        <?php if ($is_logged_in && !$client_is_inactive): ?>
                                            <a href="rent_request.php?space_id=<?= urlencode($space['Space_ID']) ?>" class="btn btn-success mt-3 w-100">
                                                <i class="bi bi-receipt"></i>Request an Invoice
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center">No units currently available.</div>';
                }
                ?>
                <div class="col-12 text-center mt-2">
                    <a href="#" id="moreUnitsBtn" class="btn btn-outline-dark rounded-0 fw-bold shadow-none">
                        More Units to come >>>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login Required Modal -->
<div class="modal fade" id="loginPopModal" tabindex="-1" aria-labelledby="loginPopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginPopModalLabel">Login Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-0">Please login first to rent a unit.</p>
            </div>
        </div>
    </div>
</div>

<!-- CURRENTLY RENTED UNITS Section -->
<h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">CURRENTLY RENTED UNITS</h2>
<div class="container">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 justify-content-center">
    <?php
    // Fetch all photos for all currently rented units (not just for logged-in client)
    $unit_ids = !empty($rented_units_display) ? array_column($rented_units_display, 'Space_ID') : [];
    $all_unit_photos = [];
    if (!empty($unit_ids)) {
      $all_unit_photos = $db->getAllUnitPhotosForUnits($unit_ids); // [space_id => [photo1, photo2, ...]]
    }

    if (!empty($rented_units_display)) {
      $modal_counter = 0;
      foreach ($rented_units_display as $rent) {
        $modal_counter++;
        $modal_id = "rentedModal" . $modal_counter;
        // Photos for this rented unit (public, not per client)
        $photos = $all_unit_photos[$rent['Space_ID']] ?? [];
        ?>
        <div class="col">
          <div class="card border-success shadow" style="max-width: 350px; margin: auto;">
            <div class="card-img-top text-center py-5" style="font-size:72px;color:#198754;">
              <i class="fa-solid fa-house-user"></i>
            </div>
            <div class="card-body text-center">
              <h5 class="mb-4"><?= htmlspecialchars($rent['Name']) ?></h5>
              <button class="btn btn-outline-primary shadow-none" data-bs-toggle="modal" data-bs-target="#<?= $modal_id ?>">
                View Details
              </button>
            </div>
          </div>
        </div>
        <!-- Modal for this unit -->
        <div class="modal fade" id="<?= $modal_id ?>" tabindex="-1" aria-labelledby="<?= $modal_id ?>Label" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="<?= $modal_id ?>Label"><?= htmlspecialchars($rent['Name']) ?> - Rented Unit Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="text-center mb-3" style="font-size:56px;color:#198754;">
                  <i class="fa-solid fa-house-user"></i>
                </div>
                <?php if (!empty($photos)): ?>
                  <div class="mb-3 d-flex flex-wrap justify-content-center">
                    <?php foreach ($photos as $photo): ?>
                      <div class="me-2 mb-2" style="display:inline-block;">
                        <img src="uploads/unit_photos/<?= htmlspecialchars($photo) ?>" class="unit-photo" style="width:120px;max-height:90px;object-fit:cover;border-radius:8px;border:2px solid #c1e3d5;" alt="Unit Photo">
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="mb-3 text-muted">No photo(s) uploaded for this unit yet.</div>
                <?php endif; ?>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item"><strong>Price:</strong> ₱<?= number_format($rent['Price'], 0) ?> per month</li>
                  <li class="list-group-item"><strong>Unit Type:</strong> <?= htmlspecialchars($rent['SpaceTypeName']) ?></li>
                  <li class="list-group-item"><strong>Location:</strong> <?= htmlspecialchars($rent['Street']) ?>, <?= htmlspecialchars($rent['Brgy']) ?>, <?= htmlspecialchars($rent['City']) ?></li>
                  <li class="list-group-item"><strong>Renter:</strong> <?= htmlspecialchars($rent['Client_fn'].' '.$rent['Client_ln']) ?></li>
                  <li class="list-group-item"><strong>Rental Period:</strong> <?= htmlspecialchars($rent['StartDate']) ?> to <?= htmlspecialchars($rent['EndDate']) ?></li>
                </ul>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php
      }
    } else {
      echo '<div class="col-12 text-center">No units currently rented.</div>';
    }
    ?>
  </div>
</div>

<!-- OUR HANDYMEN Section -->
<h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">OUR HANDYMEN</h2>
<div class="container">
  <div class="row justify-content-evenly px-lg-0 px-md-0 px-5">
    <?php
    $icon_map = [
      "CARPENTRY" => "IMG/show/CARPENTRY.png", "ELECTRICAL" => "IMG/show/ELECTRICAL.png",
      "PLUMBING" => "IMG/show/PLUMBING.png", "PAINTING" => "IMG/show/PAINTING.png",
      "APPLIANCE REPAIR" => "IMG/show/APPLIANCE.png",
    ];
    if (!empty($job_types_display)) {
      foreach ($job_types_display as $row) {
        $name_upper = strtoupper($row['JobType_Name']);
        $img_src = $icon_map[$name_upper] ?? "IMG/show/wifi.png";
        echo '<div class="col-lg-2 col-md-3 col-sm-6 text-center bg-white rounded shadow py-4 my-3 mx-2">';
        echo '<form method="get" action="handyman_type.php">';
        echo '<input type="hidden" name="jobtype_id" value="'.htmlspecialchars($row['JobType_ID']).'">';
        echo '<button type="submit" style="background:none;border:none;padding:0;">';
        echo '<img src="'.$img_src.'" width="80px" alt="'.htmlspecialchars($row['JobType_Name']).' Icon">';
        echo '<h5 class="mt-3">'.htmlspecialchars($row['JobType_Name']).'</h5>';
        echo '</button>';
        echo '</form>';
        echo '</div>';
      }
    } else {
      echo '<div class="col-12 text-center">No job types available.</div>';
    }
    ?>
  <div class="col-lg-12 text-center mt-5">
  <a href="handyman_type.php" class="btn btn-outline-dark rounded-0 fw-bold shadow-none">
    More Details >>>
  </a>
</div>
  </div>
</div>

<!-- RATINGS Section -->
<h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">RATINGS</h2>
<div class="container mt-5">
  <div class="swiper swiper-testimonials">
    <div class="swiper-wrapper mb-5">
      <?php
      if (!empty($testimonials)) {
        foreach ($testimonials as $fb) {
          $stars = str_repeat('<i class="bi bi-star-fill text-warning"></i>', $fb['Rating']);
          $stars .= str_repeat('<i class="bi bi-star"></i>', 5 - $fb['Rating']);
          echo '<div class="swiper-slide bg-white p-4">
            <div class="profile d-flex align-items-center p-4">
              <h6 class="m-0 ms-2">'.htmlspecialchars($fb['Client_fn'].' '.$fb['Client_ln']).'</h6>
            </div>
            <p class="mb-0">'.htmlspecialchars($fb['Comments']).'</p>
            <div class="rating">'.$stars.'</div>
          </div>';
        }
      } else {
        echo '<div class="swiper-slide bg-white p-4"><p>No testimonials yet.</p></div>';
      }
      ?>
    </div>
    <div class="swiper-pagination"></div>
  </div>
</div>

<!-- REACH US Section -->
<h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">REACH US</h2>
<div class="container">
  <div class="row">
    <div class="col-lg-8 col-md-8 p-4 mb-lg-0 mb-3 bg-white rounded">
      <iframe class="w-100 rounded " height="320px" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.161920992376!2d121.16322267491122!3d13.948962686463787!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd6c9ea0e9c9bf%3A0xf9daae5e3d997480!2sGen.%20Luna%20St%2C%20Lipa%2C%20Batangas!5e0!3m2!1sen!2sph!4v1748185696621!5m2!1sen!2sph" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
    <div class="col-lg-4 col-md-4">
      <div class="bg-white p-4 rounded mb-4">
        <h5>Call Us</h5>
        <a href="tel:+639123456789" class="d-inline-block mb-2 text-decoration-none text-dark">
          <i class="bi bi-telephone-fill"></i> +63 912 345 6789
        </a>
      </div>
      <div class="bg-white p-4 rounded mb-4">
        <h5>Follow Us</h5>
        <a href="#" class="d-inline-block mb-2 ">
          <span><i class="bi bi-facebook me-1"></i> Facebook</span>
        </a>
      </div>
    </div>
  </div>
</div>

<?php if (!$is_logged_in): ?>
<!-- Free Message Floating Bubble -->
<div id="freeMsgBubble" title="Send us a message">
    <i class="fas fa-comment-dots"></i>
</div>

<!-- Free Message Modal -->
<div class="modal fade" id="freeMsgModal" tabindex="-1" aria-labelledby="freeMsgModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="free_message_send.php" id="freeMsgForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="freeMsgModalLabel"><i class="fas fa-envelope-open-text me-1"></i>Ask us anything!</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Name*</label>
            <input type="text" name="client_name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Email*</label>
            <input type="email" name="client_email" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Phone</label>
            <input type="text" name="client_phone" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Your Message*</label>
            <textarea name="message_text" class="form-control" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary w-100">Send Message</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require('footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
// Swiper for main slider
var swiper1 = new Swiper('.swiper-container', {
  loop: true,
  autoplay: { delay: 3000 },
  pagination: {
    el: '.swiper-container .swiper-pagination',
    clickable: true,
  },
});

// Swiper for testimonials
var swiper2 = new Swiper('.swiper-testimonials', {
  effect: "coverflow",
  grabCursor: true,
  centeredSlides: true,
  slidesPerView: "auto",
  loop: true,
  coverflowEffect: {
    rotate: 50,
    stretch: 0,
    depth: 100,
    modifier: 1,
    slideShadows: false,
  },
  pagination: {
    el: ".swiper-testimonials .swiper-pagination",
    clickable: true,
  },
  breakpoints: {
    320: { slidesPerView: 1, spaceBetween: 20 },
    768: { slidesPerView: 2, spaceBetween: 30 },
    1024: { slidesPerView: 3, spaceBetween: 40 }
  },
});

// SweetAlert for "Soon to come" button
document.getElementById('moreUnitsBtn').addEventListener('click', function(e) {
  e.preventDefault();
  Swal.fire({
    icon: 'info',
    title: 'More Units Coming Soon!',
    text: 'We are working on adding more properties. Please check back later!',
    confirmButtonColor: '#3085d6'
  });
});

// Free Message Bubble JS
<?php if (!$is_logged_in): ?>
document.getElementById('freeMsgBubble').onclick = function() {
    var myModal = new bootstrap.Modal(document.getElementById('freeMsgModal'));
    myModal.show();
};
<?php endif; ?>
</script>
</body>
</html>