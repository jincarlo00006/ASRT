<?php
session_start();
$is_logged_in = isset($_SESSION['C_username']) && isset($_SESSION['client_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ASRT Website - ABOUT</title>
  <?php require('links.php'); ?>
  <link href="https://fonts.googleapis.com/css2?family=Merienda&family=Poppins&display=swap" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .h-font {
      font-family: 'Merienda', cursive;
      font-size: 2.5rem;
    }
    .h-line {
      width: 80px;
      height: 4px;
      background-color: #000;
      margin: 10px auto;
    }
    .box {
      border-top: 4px solid var(--teal, #20c997); /* Added a fallback color */
      transition: transform 0.3s ease-in-out;
    }
    .box:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .swiper-slide img {
      width: 220px;
      height: 220px; /* Set a fixed height for consistency */
      object-fit: cover; /* Ensures images cover the area without distortion */
      border-radius: 15px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.15);
      transition: transform 0.3s;
    }
    .swiper-slide img:hover {
      transform: scale(1.05);
    }
    /* The auth-btns style block is no longer needed and can be removed */
  </style>
</head>
<body class="bg-light">

<!-- The header will now correctly display the Login/Register/Logout buttons -->
<?php require('header.php'); ?>

<!-- The redundant auth-btns div has been removed from here. -->

<div class="my-5 px-4 text-center">
  <h2 class="fw-bold h-font">ABOUT US</h2>
  <div class="h-line"></div>
  <p class="mt-3 text-muted col-lg-8 mx-auto">
    Welcome to <strong>ASRT Commercial Spacing</strong>! We are dedicated to providing secure, reliable, and flexible commercial spaces tailored to your business needs. Our mission is to create an environment where businesses can thrive, supported by modern amenities and exceptional service.
  </p>
</div>

<div class="container">
  <div class="row justify-content-between align-items-center">
    <div class="col-lg-6 col-md-5 mb-4 order-lg-1 order-md-2 order-2">
      <h3 class="mb-3">Our Story</h3>
      <p class="text-muted">
        Founded with a vision to support local entrepreneurs, ASRT has grown into a trusted workspace provider. 
        We work hard to maintain high standards of safety, convenience, and customer satisfaction. 
        Be part of a community where your success is our priority. 
        <br><br>
        Whether you're a startup, freelancer, or an established business, our flexible space solutions are designed to adapt to your evolving needs. 
        With modern amenities, reliable maintenance support, and a vibrant network of like-minded professionals, ASRT is more than just a space—it's a launchpad for your growth. 
      </p>
    </div>
    <div class="col-lg-5 col-md-5 mb-4 text-center order-lg-2 order-md-1 order-1">
      <img src="IMG/show/asrt.jpg" class="img-fluid rounded shadow" alt="About Us Image">
      <p class="mt-3 fw-semibold">
        The proud owners of ASRT Business
      </p>
    </div>
  </div>
</div>

<div class="container mt-5">
  <div class="row text-center">
    <div class="col-lg-4 col-md-6 mb-4 px-4">
      <div class="bg-white rounded shadow p-4 box">
        <img src="IMG/show/unit.webp" width="70px" alt="Unit">
        <h5 class="mt-3">10+ Units Available</h5>
        <p class="text-muted">We provide a variety of commercial units fit for all sizes of businesses.</p>
      </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4 px-4">
      <div class="bg-white rounded shadow p-4 box">
        <img src="IMG/show/unit.webp" width="70px" alt="Experience">
        <h5 class="mt-3">Over a Decade of Service</h5>
        <p class="text-muted">Trusted by businesses for more than 10 years.</p>
      </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4 px-4">
      <div class="bg-white rounded shadow p-4 box">
        <img src="IMG/show/unit.webp" width="70px" alt="Clients">
        <h5 class="mt-3">100+ Happy Clients</h5>
        <p class="text-muted">A growing community of satisfied business owners.</p>
      </div>
    </div>
  </div>
</div>

<h3 class="text-center mt-5 mb-4">Meet Our Developers</h3>
<p class="text-center text-muted mb-4 col-lg-8 mx-auto">
  This project was proudly developed by students of <strong>NU Lipa – INF232</strong>, a team driven by innovation, collaboration, and a shared goal of creating impactful digital solutions. From design to functionality, each aspect reflects the dedication of aspiring developers shaping the future of tech.
</p>

<div class="container px-4">
  <div class="swiper mySwiper">
    <div class="swiper-wrapper mb-5">
      <!-- Swiper Slide Examples -->
      <div class="swiper-slide text-center"><img src="IMG/SHOW/BRYAN.jpg" alt="Bryan Gabriel Tesoro"><p class="mt-2 fw-semibold">Bryan Gabriel Tesoro</p></div>
      <div class="swiper-slide text-center"><img src="IMG/SHOW/LUKE.jpg" alt="Luke Aron Magpantay"><p class="mt-2 fw-semibold">Luke Aron Magpantay</p></div>
      <div class="swiper-slide text-center"><img src="IMG/SHOW/jin.jpg" alt="Jin Carlo Maullon"><p class="mt-2 fw-semibold">Jin Carlo Maullon</p></div>
      <div class="swiper-slide text-center"><img src="IMG/SHOW/kibrys.jpg" alt="John Kibry Buño"><p class="mt-2 fw-semibold">John Kibry Buño</p></div>
      <div class="swiper-slide text-center"><img src="IMG/SHOW/romeo.jpg" alt="Romeo Paolo Tolentino"><p class="mt-2 fw-semibold">Romeo Paolo Tolentino</p></div>
    </div>
    <div class="swiper-pagination"></div>
  </div>
</div>

<?php require('footer.php'); ?>

<!-- Redundant Login/Register Modals have been removed from here -->

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script>
  var swiper = new Swiper(".mySwiper", {
    slidesPerView: 1, // Start with 1 on small screens
    spaceBetween: 30,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    // Responsive breakpoints
    breakpoints: {
      640: { slidesPerView: 2, spaceBetween: 20, },
      768: { slidesPerView: 3, spaceBetween: 40, },
      1024: { slidesPerView: 4, spaceBetween: 50, },
      1200: { slidesPerView: 5, spaceBetween: 30, }
    }
  });
</script>

</body>
</html>