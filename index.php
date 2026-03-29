<?php

declare(strict_types=1);

$title = 'Blood Bank Management System';
$active = 'home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Blood Bank Management System</h1>
    <p>Find blood quickly, help donors connect, and manage requests in one place.</p>
</section>

<section class="grid four-cards">
    <a class="card" href="/WHERE-IS-MY-BLOOD/admin/login.php">
        <h2>Admin</h2>
        <p>Login to manage blood inventory, donors, and incoming requests.</p>
    </a>

    <a class="card" href="/WHERE-IS-MY-BLOOD/user/login.php">
        <h2>User Login</h2>
        <p>Login and get a personal welcome before submitting requests.</p>
    </a>

    <a class="card" href="/WHERE-IS-MY-BLOOD/contact.php">
        <h2>Contact</h2>
        <p>Get in touch with the blood bank support team.</p>
    </a>

    <a class="card" href="/WHERE-IS-MY-BLOOD/location.php">
        <h2>Location</h2>
        <p>See blood bank branches and emergency contact points.</p>
    </a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
