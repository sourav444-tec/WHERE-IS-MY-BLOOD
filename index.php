<?php

declare(strict_types=1);

$title = 'Blood Bank Management System';
$active = 'home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <p class="hero-kicker">Emergency Ready Platform</p>
    <h1>Blood Bank Management System</h1>
    <p>Find blood quickly, help donors connect, and manage requests in one place.</p>
    <div class="hero-actions">
        <a class="link-button" href="/WHERE-IS-MY-BLOOD/user/login.php">Request Blood</a>
        <a class="link-button" href="/WHERE-IS-MY-BLOOD/user/buy_blood.php">Buy Blood</a>
    </div>
</section>

<section class="grid four-cards home-features">
    <a class="card feature-card" href="/WHERE-IS-MY-BLOOD/admin/login.php">
        <span class="feature-tag">Control Center</span>
        <h2>Admin</h2>
        <p>Login to manage blood inventory, donors, and incoming requests.</p>
    </a>

    <a class="card feature-card" href="/WHERE-IS-MY-BLOOD/user/login.php">
        <span class="feature-tag">Secure Access</span>
        <h2>User Login</h2>
        <p>Login and get a personal welcome before submitting requests.</p>
    </a>

    <a class="card feature-card" href="/WHERE-IS-MY-BLOOD/contact.php">
        <span class="feature-tag">Support Desk</span>
        <h2>Contact</h2>
        <p>Get in touch with the blood bank support team.</p>
    </a>

    <a class="card feature-card" href="/WHERE-IS-MY-BLOOD/location.php">
        <span class="feature-tag">Centers Nearby</span>
        <h2>Location</h2>
        <p>See blood bank branches and emergency contact points.</p>
    </a>

    <a class="card feature-card" href="/WHERE-IS-MY-BLOOD/user/buy_blood.php">
        <span class="feature-tag">Fast Delivery</span>
        <h2>Buy Blood</h2>
        <p>Login and place blood purchase orders with order tracking status.</p>
    </a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
